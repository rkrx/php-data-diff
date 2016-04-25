<?php
namespace DataDiff;

use Generator;
use PDO;
use PDOStatement;
use Traversable;

class DiffStorageStore implements DiffStorageStoreInterface {
	/** @var PDO */
	private $pdo;
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
	/** @var int */
	private $counter = 0;
	/** @var callable */
	private $duplicateKeyHandler;
	/** @var array */
	private $converter;
	/** @var string[] */
	private $keys;
	/** @var string[] */
	private $valueKeys;

	/**
	 * @param PDO $pdo
	 * @param string $keySchema
	 * @param string $valueSchema
	 * @param string[] $keys
	 * @param string[] $valueKeys
	 * @param array $converter
	 * @param string $storeA
	 * @param string $storeB
	 * @param callable $duplicateKeyHandler
	 */
	public function __construct(PDO $pdo, $keySchema, $valueSchema, array $keys, array $valueKeys, array $converter, $storeA, $storeB, $duplicateKeyHandler) {
		$this->pdo = $pdo;
		$this->selectStmt = $this->pdo->prepare("SELECT s_data FROM data_store WHERE s_ab='{$storeA}' AND s_key={$keySchema} AND (1=1 OR s_value={$valueSchema})");
		$this->insertStmt = $this->pdo->prepare("INSERT INTO data_store (s_ab, s_key, s_value, s_data, s_sort) VALUES ('{$storeA}', {$keySchema}, {$valueSchema}, :___data, :___sort)");
		$this->updateStmt = $this->pdo->prepare("UPDATE data_store SET s_value={$valueSchema}, s_data=:___data WHERE s_ab='{$storeA}' AND s_key={$keySchema}");
		$this->storeA = $storeA;
		$this->storeB = $storeB;
		$this->keys = $keys;
		$this->valueKeys = $valueKeys;
		$this->converter = $converter;
		$this->duplicateKeyHandler = $duplicateKeyHandler;
	}

	/**
	 * @param array $data
	 * @param array $translation
	 * @param callable $duplicateKeyHandler
	 */
	public function addRow(array $data, array $translation = null, $duplicateKeyHandler = null) {
		$data = $this->translate($data, $translation);
		if($duplicateKeyHandler === null) {
			$duplicateKeyHandler = $this->duplicateKeyHandler;
		}
		$buildMetaData = function (array $data, array $keys) {
			$metaData = $data;
			$metaData = array_diff_key($metaData, array_diff_key($metaData, $keys));
			$metaData['___data'] = json_encode($data);
			$metaData['___sort'] = $this->counter;
			return $metaData;
		};
		try {
			$metaData = $buildMetaData($data, $this->converter);
			$this->insertStmt->execute($metaData);
		} catch (\PDOException $e) {
			if(strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
				$metaData = $buildMetaData($data, $this->converter);
				unset($metaData['___data']);
				unset($metaData['___sort']);
				$this->selectStmt->execute($metaData);
				$oldData = $this->selectStmt->fetch(PDO::FETCH_COLUMN, 0);
				if($oldData === null) {
					$oldData = [];
				} else {
					$oldData = json_decode($oldData, true);
				}
				$data = $duplicateKeyHandler($data, $oldData);
				$metaData = $buildMetaData($data, $this->converter);
				unset($metaData['___sort']);
				$this->updateStmt->execute($metaData);
			} else {
				throw $e;
			}
		}
	}

	/**
	 * @param Traversable|array $rows
	 * @param array $translation
	 * @param callable $duplicateKeyHandler
	 * @return $this
	 */
	public function addRows($rows, array $translation = null, $duplicateKeyHandler = null) {
		foreach($rows as $row) {
			$this->addRow($row, $translation, $duplicateKeyHandler);
		}
		return $this;
	}

	/**
	 * Returns true whenever there is any changed, added or removed data available
	 */
	public function hasAnyChanges() {
		/** @noinspection PhpUnusedLocalVariableInspection */
		foreach($this->getNewOrChanged() as $_) {
			return true;
		}
		/** @noinspection PhpUnusedLocalVariableInspection */
		foreach($this->getMissing() as $_) {
			return true;
		}
		return false;
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
			$d = json_decode($row[1], true);
			$f = json_decode($row[2], true);
			yield $this->instantiateRow($d, $f);
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
			$row = $this->instantiateRow($row, []);
			yield $row->getData();
		}
		$stmt->closeCursor();
	}

	/**
	 * @param array $data
	 * @param array $translation
	 * @return array
	 */
	private function translate(array $data, array $translation = null) {
		if($translation !== null) {
			$result = [];
			foreach($data as $key => $value) {
				if(array_key_exists($key, $translation)) {
					$key = $translation[$key];
				}
				$result[$key] = $value;
			}
			return $result;
		}
		return $data;
	}

	/**
	 * @return int
	 */
	public function count() {
		$query = '
			SELECT
				COUNT(*)
			FROM
				data_store AS s1
			WHERE
				s1.s_ab = :s
		';
		$stmt = $this->pdo->query($query);
		$stmt->execute(['s' => $this->storeA]);
		$count = $stmt->fetch(PDO::FETCH_COLUMN, 0);
		return $count;
	}

	/**
	 * @param array $localData
	 * @param array $foreignData
	 * @return DiffStorageStoreRow
	 */
	private function instantiateRow(array $localData = null, array $foreignData = null) {
		return new DiffStorageStoreRow($localData, $foreignData, $this->keys, $this->valueKeys, $this->converter);
	}
}
