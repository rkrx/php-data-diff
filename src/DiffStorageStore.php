<?php
namespace DataDiff;

use DataDiff\Tools\Json;
use DataDiff\Tools\PDOTools;
use DataDiff\Tools\StringTools;
use Generator;
use JsonSerializable;
use PDO;
use PDOException;
use PDOStatement;
use stdClass;

class DiffStorageStore implements DiffStorageStoreInterface {
	/** @var PDO */
	private $pdo;
	/** @var PDOStatement */
	private $insertStmt;
	/** @var PDOStatement */
	private $replaceStmt;
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
	/** @var null|callable(array<string, null|scalar>, array<string, null|scalar>): array<string, null|scalar> */
	private $duplicateKeyHandler;
	/** @var array<string, callable(mixed): (scalar|null)> */
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
	 * @param array<string, callable(mixed): (scalar|null)> $converter
	 * @param string $storeA
	 * @param string $storeB
	 * @param null|callable(array<string, null|scalar>, array<string, null|scalar>): array<string, null|scalar> $duplicateKeyHandler
	 */
	public function __construct(PDO $pdo, string $keySchema, string $valueSchema, array $keys, array $valueKeys, array $converter, string $storeA, string $storeB, ?callable $duplicateKeyHandler) {
		$this->pdo = $pdo;
		$this->selectStmt = $this->pdo->prepare("SELECT s_data FROM data_store WHERE s_ab='{$storeA}' AND s_key={$keySchema} AND (1=1 OR s_value={$valueSchema})");
		$this->replaceStmt = $this->pdo->prepare("INSERT OR REPLACE INTO data_store (s_ab, s_key, s_value, s_data, s_sort) VALUES ('{$storeA}', {$keySchema}, {$valueSchema}, :___data, :___sort)");
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
	 * @inheritDoc
	 */
	public function addRow(array $data, ?array $translation = null, ?callable $duplicateKeyHandler = null): void {
		$data = $this->translate($data, $translation);
		if($duplicateKeyHandler === null) {
			$duplicateKeyHandler = $this->duplicateKeyHandler;
		}
		$metaData = $this->buildMetaData($data);
		/** @var callable|null $duplicateKeyHandler */
		if($duplicateKeyHandler === null) {
			$this->replaceStmt->execute($metaData);
		} else {
			try {
				$this->insertStmt->execute($metaData);
			} catch (PDOException $e) {
				if(strpos($e->getMessage(), 'SQLSTATE[23000]') !== false) {
					$metaData = $this->buildMetaData($data);
					unset($metaData['___data'], $metaData['___sort']);
					$this->selectStmt->execute($metaData);
					$oldData = $this->selectStmt->fetch(PDO::FETCH_COLUMN, 0);
					if($oldData === null) {
						$oldData = [];
					} else {
						$oldData = unserialize($oldData);
					}
					$data = $duplicateKeyHandler($data, $oldData);
					$metaData = $this->buildMetaData($data);
					unset($metaData['___sort']);
					$this->updateStmt->execute($metaData);
				} else {
					throw $e;
				}
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public function addRows($rows, ?array $translation = null, ?callable $duplicateKeyHandler = null) {
		foreach($rows as $row) {
			if($row instanceof stdClass) {
				$row = (array) $row;
			} elseif($row instanceof JsonSerializable) {
				$row = $row->jsonSerialize();
			}
			$this->addRow($row, $translation, $duplicateKeyHandler);
		}
		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function hasAnyChanges(): bool {
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
	 * @inheritDoc
	 */
	public function getUnchanged(array $arguments = []) {
		$limit = array_key_exists('limit', $arguments) ? sprintf("LIMIT %d", $arguments['limit']) : "";
		return $this->query("
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
				s1.s_value = s2.s_value
			ORDER BY
				s1.s_sort
			{$limit}
		", function (DiffStorageStoreRowInterface $row) {
			return $this->formatUnchangedRow($row);
		});
	}

	/**
	 * @inheritDoc
	 */
	public function getNew(array $arguments = []) {
		$limit = array_key_exists('limit', $arguments) ? sprintf("LIMIT %d", $arguments['limit']) : "";
		return $this->query("
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
			{$limit}
		", function (DiffStorageStoreRowInterface $row) {
			return $this->formatNewRow($row);
		});
	}

	/**
	 * @inheritDoc
	 */
	public function getChanged(array $arguments = []) {
		$limit = array_key_exists('limit', $arguments) ? sprintf("LIMIT %d", $arguments['limit']) : "";
		return $this->query("
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
			{$limit}
		", function (DiffStorageStoreRowInterface $row) {
			return $this->formatChangedRow($row);
		});
	}

	/**
	 * @inheritDoc
	 */
	public function getNewOrChanged(array $arguments = []) {
		$limit = array_key_exists('limit', $arguments) ? sprintf("LIMIT %d", $arguments['limit']) : "";
		return $this->query("
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
			{$limit}
		", function (DiffStorageStoreRowInterface $row) {
			if(count($row->getForeign()->getValueData())) {
				return $this->formatChangedRow($row);
			}
			return $this->formatNewRow($row);
		});
	}

	/**
	 * @inheritDoc
	 */
	public function getMissing(array $arguments = []) {
		$limit = array_key_exists('limit', $arguments) ? sprintf("LIMIT %d", $arguments['limit']) : "";
		return $this->query("
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
			{$limit}
		", function (DiffStorageStoreRowInterface $row) {
			return $this->formatMissingRow($row);
		});
	}

	/**
	 * @inheritDoc
	 */
	public function getNewOrChangedOrMissing(array $arguments = []) {
		// Do not use `yield from` here, since the key (index) will start at 0 with getMissing()
		foreach($this->getNewOrChanged($arguments) as $row) {
			yield $row;
		}
		foreach($this->getMissing($arguments) as $row) {
			yield $row;
		}
	}

	/**
	 * @return $this
	 */
	public function clearAll() {
		$stmt = $this->pdo->query('DELETE FROM data_store WHERE s_ab=:s');
		return PDOTools::useStmt($stmt, function (PDOStatement $stmt) {
			$stmt->execute(['s' => $this->storeA]);
			return $this;
		});
	}

	/**
	 * @param string $query
	 * @param callable $stringFormatter
	 * @return Generator<int, DiffStorageStoreRow>
	 */
	private function query(string $query, callable $stringFormatter) {
		$stmt = $this->pdo->query($query);
		yield from PDOTools::useStmt($stmt, function (PDOStatement $stmt) use ($stringFormatter) {
			$stmt->execute(['sA' => $this->storeA, 'sB' => $this->storeB]);
			while($row = $stmt->fetch(PDO::FETCH_NUM)) {
				$d = unserialize($row[1]);
				$f = unserialize($row[2]);
				yield $this->instantiateRow($d !== false ? $d : null, $f !== false ? $f : null, $stringFormatter);
			}
		});
	}

	/**
	 * @inheritDoc
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
		yield from PDOTools::useStmt($stmt, function (PDOStatement $stmt) {
			$stmt->execute(['s' => $this->storeA]);
			while($row = $stmt->fetch(PDO::FETCH_NUM)) {
				$row = unserialize($row[0]);
				$row = $this->instantiateRow($row, [], function (DiffStorageStoreRowInterface $row) {
					return $this->formatKeyValuePairs($row->getData());
				});
				yield $row->getData();
			}
		});
	}

	/**
	 * @param array<string, mixed> $data
	 * @param null|array<string, string> $translation
	 * @return array<string, string>
	 */
	private function translate(array $data, ?array $translation = null): array {
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
	public function count(): int {
		$query = 'SELECT COUNT(*) FROM data_store AS s1 WHERE s1.s_ab = :s';
		$stmt = $this->pdo->query($query);
		return PDOTools::useStmt($stmt, function (PDOStatement $stmt) {
			$stmt->execute(['s' => $this->storeA]);
			return (int) $stmt->fetch(PDO::FETCH_COLUMN, 0);
		});
	}

	/**
	 * @param null|array<string, mixed> $localData
	 * @param null|array<string, mixed> $foreignData
	 * @param callable $stringFormatter
	 * @return DiffStorageStoreRow
	 */
	private function instantiateRow(?array $localData, ?array $foreignData, callable $stringFormatter): DiffStorageStoreRow {
		return new DiffStorageStoreRow($localData, $foreignData, $this->keys, $this->valueKeys, $this->converter, $stringFormatter);
	}

	/**
	 * @param DiffStorageStoreRowInterface $row
	 * @return string
	 */
	private function formatNewRow(DiffStorageStoreRowInterface $row): string {
		$keys = $this->formatKeyValuePairs($row->getLocal()->getKeyData(), false);
		$values = $this->formatKeyValuePairs($row->getLocal()->getValueData());
		return sprintf("New %s (%s)", $keys, $values);
	}

	/**
	 * @param DiffStorageStoreRowInterface $row
	 * @return string
	 */
	private function formatUnchangedRow(DiffStorageStoreRowInterface $row): string {
		$keys = $this->formatKeyValuePairs($row->getLocal()->getKeyData(), false);
		return sprintf("Unchanged %s", $keys);
	}

	/**
	 * @param DiffStorageStoreRowInterface $row
	 * @return string
	 */
	private function formatChangedRow(DiffStorageStoreRowInterface $row): string {
		$keys = $this->formatKeyValuePairs($row->getLocal()->getKeyData(), false);
		return sprintf("Changed %s => %s", $keys, $row->getDiffFormatted($this->valueKeys));
	}

	/**
	 * @param DiffStorageStoreRowInterface $row
	 * @return string
	 */
	private function formatMissingRow(DiffStorageStoreRowInterface $row): string {
		$keys = $this->formatKeyValuePairs($row->getForeign()->getKeyData(), false);
		$values = $this->formatKeyValuePairs($row->getForeign()->getValueData());
		return sprintf("Missing %s (%s)", $keys, $values);
	}

	/**
	 * @param array<string, string> $keyValues
	 * @param bool $shortenLongValues
	 * @return string
	 */
	private function formatKeyValuePairs(array $keyValues, bool $shortenLongValues = true): string {
		$keyParts = [];
		foreach($keyValues as $key => $value) {
			if(is_string($value) && $shortenLongValues) {
				$value = StringTools::shorten($value);
			}
			$keyParts[] = sprintf("%s: %s", $key, Json::encode($value));
		}
		return implode(', ', $keyParts);
	}

	/**
	 * @param array<string, mixed> $data
	 * @return array<string, string>
	 */
	private function buildMetaData(array $data): array {
		$metaData = $data;
		$metaData = array_diff_key($metaData, array_diff_key($metaData, $this->converter));
		$metaData['___data'] = serialize($data);
		$metaData['___sort'] = $this->counter;
		return $metaData;
	}
}
