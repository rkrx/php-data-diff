<?php
namespace DataDiff;

use Exception;
use Generator;
use PDO;
use PDOStatement;

class DiffStorageStore {
	/** @var PDO */
	private $pdo;
	/** @var PDOStatement */
	private $testStmt;
	/** @var PDOStatement */
	private $insertStmt;
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

	/**
	 * @param PDO $pdo
	 * @param array $schema
	 * @param array $valueSchema
	 * @param mixed $missingColumnValue
	 * @param callable $duplicateKeyHandler
	 * @param string $storeA
	 * @param string $storeB
	 */
	public function __construct(PDO $pdo, array $schema, array $valueSchema, $missingColumnValue, $duplicateKeyHandler, $storeA, $storeB) {
		$this->pdo = $pdo;
		$this->testStmt = $this->pdo->prepare('SELECT COUNT(*) FROM data_store WHERE s_ab=:s AND s_key=:k');
		$this->selectStmt = $this->pdo->prepare('SELECT s_data FROM data_store WHERE s_ab=:s AND s_key=:k');
		$this->insertStmt = $this->pdo->prepare('INSERT INTO data_store (s_ab, s_key, s_data_hash, s_data, s_sort) VALUES (:s, :kH, :vH, :v, :srt)');
		$this->updateStmt = $this->pdo->prepare('UPDATE data_store SET s_data_hash=:vH, s_data=:v WHERE s_ab=:s AND s_key=:kH');
		$this->storeA = $storeA;
		$this->storeB = $storeB;
		$this->keySchema = $schema;
		$this->dataSchema = $valueSchema;
		$this->missingColumnValue = $missingColumnValue;
		$this->duplicateKeyHandler = $duplicateKeyHandler;
	}

	/**
	 * @param array $data
	 * @throws Exception
	 */
	public function addRow(array $data) {
		$keyHash = $this->convertData($data, $this->keySchema);
		$dataHash = $this->convertData($data, $this->dataSchema);
		$this->testStmt->execute(['s' => $this->storeA, 'k' => $keyHash]);
		$count = $this->testStmt->fetch(PDO::FETCH_COLUMN, 0);
		if($count < 1) {
			$this->counter++;
			$this->insertStmt->execute(['s' => $this->storeA, 'kH' => $keyHash, 'vH' => $dataHash, 'v' => json_encode($data, JSON_UNESCAPED_SLASHES), 'srt' => $this->counter]);
		} else {
			$this->selectStmt->execute(['s' => $this->storeA, 'k' => $keyHash]);
			$oldData = $this->selectStmt->fetch(PDO::FETCH_COLUMN, 0);
			$oldData = json_decode($oldData, true);
			$data = call_user_func($this->duplicateKeyHandler, $data, $oldData);
			$this->updateStmt->execute(['s' => $this->storeA, 'kH' => $keyHash, 'vH' => $dataHash, 'v' => json_encode($data, JSON_UNESCAPED_SLASHES)]);
		}
	}

	/**
	 * Get all rows, that are present in this store, but not in the other
	 *
	 * @return array[]
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
	 * @return array[]
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
				s1.s_data_hash != s2.s_data_hash
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
				((s2.s_ab IS NULL) OR (s1.s_data_hash != s2.s_data_hash))
			ORDER BY
				s1.s_sort
		');
	}

	/**
	 * Get all rows, that are present in the other store, but not in this
	 *
	 * @return array[]
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
	 * @param array $data
	 * @param array $schema
	 * @return string
	 */
	private function convertData(array $data, array $schema) {
		$index = [];
		foreach($schema as $key => $converter) {
			$value = $this->missingColumnValue;
			if(array_key_exists($key, $data)) {
				$value = $data[$key];
			}
			$value = call_user_func($converter, $value);
			$index[$key] = $value;
		}
		$key = sha1(json_encode($index, JSON_UNESCAPED_SLASHES));
		return $key;
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
			yield $k => new DiffStorageStoreRow($d, $f);
		}
		$stmt->closeCursor();
	}
}
