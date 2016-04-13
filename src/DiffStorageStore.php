<?php
namespace DataDiff;

use Exception;
use Generator;
use PDO;
use PDOStatement;
use Traversable;

class DiffStorageStore implements \IteratorAggregate {
	/** @var PDO */
	private $pdo;
	/** @var PDOStatement */
	private $testStmt;
	/** @var PDOStatement */
	private $insertStmt;
	/** @var PDOStatement */
	private $selectStmt;
	/** @var PDOStatement */
	private $updateStmt;
	/** @var string */
	private $storeA;
	/** @var string */
	private $storeB;
	/** @var mixed */
	private $missingColumnValue;
	/** @var array */
	private $keySchema;
	/** @var array */
	private $dataSchema;
	/** @var int */
	private $counter = 0;
	/** @var callable */
	private $duplicateKeyHandler;
	/** @var array */
	private $converter;

	/**
	 * @param PDO $pdo
	 * @param string $keySchema
	 * @param string $valueSchema
	 * @param array $converter
	 * @param mixed $missingColumnValue
	 * @param callable $duplicateKeyHandler
	 * @param string $storeA
	 * @param string $storeB
	 */
	public function __construct(PDO $pdo, $keySchema, $valueSchema, array $converter, $missingColumnValue, $duplicateKeyHandler, $storeA, $storeB) {
		$this->pdo = $pdo;
		$this->testStmt = $this->pdo->prepare('SELECT COUNT(*) FROM data_store WHERE s_ab=:s AND s_key=:k');
		$this->selectStmt = $this->pdo->prepare('SELECT s_data FROM data_store WHERE s_ab=:s AND s_key=:k');
		$this->insertStmt = $this->pdo->prepare("INSERT INTO data_store (s_ab, s_key, s_value, s_data, s_sort) VALUES ('{$storeA}', {$keySchema}, {$valueSchema}, :___data, :___sort)");
		$this->updateStmt = $this->pdo->prepare("UPDATE data_store SET s_value={$valueSchema}, s_data=:___data WHERE s_ab='{$storeA}' AND s_key={$keySchema}");
		$this->storeA = $storeA;
		$this->storeB = $storeB;
		$this->keySchema = $keySchema;
		$this->dataSchema = $valueSchema;
		$this->converter = $converter;
		$this->missingColumnValue = $missingColumnValue;
		$this->duplicateKeyHandler = $duplicateKeyHandler;
	}

	/**
	 * @param array $data
	 * @param callable $duplicateKeyHandler
	 */
	public function addRow(array $data, $duplicateKeyHandler = null) {
		$metaData = $data;
		$metaData = array_diff_key($metaData, array_diff_key($metaData, $this->converter));
		$metaData['___data'] = json_encode($data);
		$metaData['___sort'] = $this->counter;
		try {
			$this->insertStmt->execute($metaData);
		} catch (\PDOException $e) {
			if(strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
				unset($metaData['___sort']);
				$this->updateStmt->execute($metaData);
			} else {
				throw $e;
			}
		}
	}

	/**
	 * Get all rows, that are present in this store, but not in the other
	 *
	 * @return Generator|DiffStorageStoreRow[]
	 */
	public function getNew() {
		return $this->query('
			SELECT
				s1.s_key AS k,
				s1.s_data AS d,
				s2.s_data AS f
			FROM
				data_store AS s1
			LEFT JOIN
				data_store AS s2 ON s2.s_ab = :sB AND s1.s_key = s2.s_key
			WHERE
				s1.s_ab = :sA
				AND
				s2.s_ab IS NULL
			ORDER BY
				s1.s_sort
		');
	}

	/**
	 * Get all rows, that have a different value hash in the other store
	 *
	 * @return Generator|DiffStorageStoreRow[]
	 */
	public function getChanged() {
		return $this->query('
			SELECT
				s1.s_key AS k,
				s1.s_data AS d,
				s2.s_data AS f
			FROM
				data_store AS s1
			INNER JOIN
				data_store AS s2 ON s2.s_ab = :sB AND s1.s_key = s2.s_key
			WHERE
				s1.s_ab = :sA
				AND
				s1.s_value != s2.s_value
			ORDER BY
				s1.s_sort
		');
	}

	/**
	 * @return Generator|DiffStorageStoreRow[]
	 */
	public function getNewOrChanged() {
		return $this->query('
			SELECT
				s1.s_key AS k,
				s1.s_data AS d,
				s2.s_data AS f
			FROM
				data_store AS s1
			LEFT JOIN
				data_store AS s2 ON s2.s_ab = :sB AND s1.s_key = s2.s_key
			WHERE
				s1.s_ab = :sA
				AND
				((s2.s_ab IS NULL) OR (s1.s_value != s2.s_value))
			ORDER BY
				s1.s_sort
		');
	}

	/**
	 * Get all rows, that are present in the other store, but not in this
	 *
	 * @return Generator|DiffStorageStoreRow[]
	 */
	public function getMissing() {
		return $this->query('
			SELECT
				s1.s_key AS k,
				s2.s_data AS d,
				s1.s_data AS f
			FROM
				data_store AS s1
			LEFT JOIN
				data_store AS s2 ON s2.s_ab = :sA AND s2.s_key = s1.s_key
			WHERE
				s1.s_ab = :sB
				AND
				s2.s_ab IS NULL
			ORDER BY
				s1.s_sort
		');
	}

	/**
	 * @return $this
	 */
	public function clearAll() {
		$stmt = $this->pdo->query('DELETE FROM data_store WHERE s_ab=:s');
		$stmt->execute(['s' => $this->storeA]);
		$stmt->closeCursor();
	}

	/**
	 * @param string $query
	 * @return Generator|DiffStorageStoreRow[]
	 */
	private function query($query) {
		$stmt = $this->pdo->query($query);
		$stmt->execute(['sA' => $this->storeA, 'sB' => $this->storeB]);
		while($row = $stmt->fetch(PDO::FETCH_NUM)) {
			$k = json_decode($row[0], true);
			$d = json_decode($row[1], true);
			$f = json_decode($row[2], true);
			yield $k => new DiffStorageStoreRow($d, $f, $this->converter, $this->missingColumnValue);
		}
		$stmt->closeCursor();
	}

	/**
	 * @return Traversable|array[]
	 */
	public function getIterator() {
		$query = '
			SELECT
				s1.s_data AS d
			FROM
				data_store AS s1
			WHERE
				s1.s_ab = :s
			ORDER BY
				s1.s_sort
		';
		$stmt = $this->pdo->query($query);
		$stmt->execute(['s' => $this->storeA]);
		while($row = $stmt->fetch(PDO::FETCH_NUM)) {
			$row = json_decode($row[0], true);
			$row = new DiffStorageStoreRow($row, [], $this->converter, $this->missingColumnValue);
			yield $row->getData();
		}
		$stmt->closeCursor();
	}
}
