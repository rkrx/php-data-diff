<?php

namespace DataDiff;

use DataDiff\Tools\Json;
use DataDiff\Tools\ModelTools;
use DataDiff\Tools\PDOTools;
use DataDiff\Tools\StringTools;
use DateTimeInterface;
use Generator;
use JsonSerializable;
use PDO;
use PDOException;
use PDOStatement;
use stdClass;
use Traversable;

/**
 * @template TKeySpec of array<string, mixed>
 * @phpstan-type TAnyKeyOfKeySpec key-of<TKeySpec>
 *
 * @template TValueSpec of array<string, mixed>
 * @phpstan-type TAnyKeyOfValueSpec key-of<TValueSpec>
 *
 * @template TFullSpec of array<string, mixed>
 *
 * @phpstan-import-type TConverter from DiffStorageStoreRowDataInterface
 *
 * @implements DiffStorageStoreInterface<TKeySpec, TValueSpec, TFullSpec, TFullSpec>
 */
class DiffStorageStore implements DiffStorageStoreInterface {
	private PDO $pdo;
	private PDOStatement $insertStmt;
	private PDOStatement $replaceStmt;
	private PDOStatement $selectStmt;
	private PDOStatement $updateStmt;
	private string $storeA;
	private string $storeB;
	private int $counter = 0;
	/** @var null|callable(TKeySpec, TValueSpec): array<string, null|scalar> */
	private $duplicateKeyHandler;
	/** @var array<string, TConverter> */
	private array $converter;
	/** @var string[] */
	private array $keys;
	/** @var string[] */
	private array $valueKeys;

	/**
	 * @param PDO $pdo
	 * @param string $keySchema
	 * @param string $valueSchema
	 * @param string[] $keys
	 * @param string[] $valueKeys
	 * @param array<string, TConverter> $converter
	 * @param string $storeA
	 * @param string $storeB
	 * @param null|callable(TKeySpec, TValueSpec): array<string, null|scalar> $duplicateKeyHandler
	 */
	public function __construct(
		PDO $pdo,
		string $keySchema,
		string $valueSchema,
		array $keys,
		array $valueKeys,
		array $converter,
		string $storeA,
		string $storeB,
		?callable $duplicateKeyHandler,
	) {
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
	 * @param array<string, mixed> $data
	 * @param null|array<string, string> $translation
	 * @param null|callable(TKeySpec, TValueSpec): array<string, null|scalar> $duplicateKeyHandler
	 */
	public function addRow(array $data, ?array $translation = null, ?callable $duplicateKeyHandler = null): void {
		$data = $this->translate($data, $translation);
		if($duplicateKeyHandler === null) {
			$duplicateKeyHandler = $this->duplicateKeyHandler;
		}
		$this->counter++;
		$metaData = $this->buildMetaData($data);
		/** @var callable|null $duplicateKeyHandler */
		if($duplicateKeyHandler === null) {
			$this->replaceStmt->execute($metaData);
		} else {
			try {
				$this->insertStmt->execute($metaData);
			} catch(PDOException $e) {
				if(strpos($e->getMessage(), 'SQLSTATE[23000]') !== false) {
					$metaData = $this->buildMetaData($data);
					unset($metaData['___data'], $metaData['___sort']);
					$this->selectStmt->execute($metaData);
					/** @var string|null $oldData */
					$oldData = $this->selectStmt->fetch(PDO::FETCH_COLUMN, 0);
					$oldData = is_string($oldData) ? self::unserialize($oldData) : [];
					$data = $duplicateKeyHandler($data, $oldData);
					// @phpstan-ignore-next-line
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
	 * @param Generator<array<string, mixed>|object>|iterable<array<string, mixed>|object> $rows
	 * @param null|array<string, string> $translation
	 * @param null|callable(TKeySpec, TValueSpec): array<string, null|scalar> $duplicateKeyHandler
	 * @return $this
	 */
	public function addRows($rows, ?array $translation = null, ?callable $duplicateKeyHandler = null) {
		foreach($rows as $row) {
			if($row instanceof stdClass) {
				$row = (array) $row;
			} elseif($row instanceof JsonSerializable) {
				$row = $row->jsonSerialize();
			}
			/** @var TFullSpec $row */
			$this->addRow($row, $translation, $duplicateKeyHandler);
		}

		return $this;
	}

	/**
	 * @template M of object
	 * @param object $model
	 * @param class-string<M>|null $className
	 * @return $this
	 */
	public function addAnnotatedModel($model, ?string $className = null) {
		/** @var TFullSpec $data */
		$data = ModelTools::getValuesFromModel($model, $className);
		$this->addRow($data);

		return $this;
	}

	/**
	 * Returns true whenever there is any changed, added or removed data available
	 *
	 * @return bool
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
	 * Get all rows, that have a different value hash in the other store
	 *
	 * @param array{limit?: int} $arguments
	 * @return Traversable<DiffStorageStoreRow<TKeySpec, TValueSpec, TFullSpec, TFullSpec>>
	 */
	public function getUnchanged(array $arguments = []): Traversable {
		$limit = array_key_exists('limit', $arguments) ? sprintf("LIMIT %d", (string) $arguments['limit']) : "";

		return $this->query(
			query: "
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
			",
			stringFormatter: fn(DiffStorageStoreRowInterface $row) => $this->formatUnchangedRow($row)
		);
	}

	/**
	 * Get all rows, that are present in this store, but not in the other
	 *
	 * @param array<string, int|string> $arguments
	 * @return Traversable<DiffStorageStoreRow<TKeySpec, TValueSpec, TFullSpec, TFullSpec>>
	 */
	public function getNew(array $arguments = []) {
		$limit = array_key_exists('limit', $arguments) ? sprintf("LIMIT %d", $arguments['limit']) : "";

		return $this->query(
			query: "
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
			",
			stringFormatter: fn (DiffStorageStoreRowInterface $row) => $this->formatNewRow($row)
		);
	}

	/**
	 * Get all rows, that have a different value hash in the other store
	 *
	 * @param array<string, int|string> $arguments
	 * @return Traversable<DiffStorageStoreRow<TKeySpec, TValueSpec, TFullSpec, TFullSpec>>
	 */
	public function getChanged(array $arguments = []) {
		$limit = array_key_exists('limit', $arguments) ? sprintf("LIMIT %d", $arguments['limit']) : "";

		return $this->query(
			query: "
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
			",
			stringFormatter: fn (DiffStorageStoreRowInterface $row) => $this->formatChangedRow($row)
		);
	}

	/**
	 * Get all rows, that are present in this store, but not in the other and
	 * get all rows, that have a different value hash in the other store
	 *
	 * @param array<string, int|string> $arguments
	 * @return Traversable<DiffStorageStoreRow<TKeySpec, TValueSpec, TFullSpec, TFullSpec>>
	 */
	public function getNewOrChanged(array $arguments = []) {
		$limit = array_key_exists('limit', $arguments) ? sprintf("LIMIT %d", $arguments['limit']) : "";

		return $this->query(
			query: "
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
			",
			stringFormatter: function (DiffStorageStoreRowInterface $row) {
				if(count($row->getForeign()->getValueData())) {
					return $this->formatChangedRow($row);
				}

				return $this->formatNewRow($row);
			}
		);
	}

	/**
	 * Get all rows, that are present in the other store, but not in this
	 *
	 * @param array<string, int|string> $arguments
	 * @return Traversable<DiffStorageStoreRow<TKeySpec, TValueSpec, TFullSpec, TFullSpec>>
	 */
	public function getMissing(array $arguments = []) {
		$limit = array_key_exists('limit', $arguments) ? sprintf("LIMIT %d", $arguments['limit']) : "";

		return $this->query(
			query: "
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
			",
			stringFormatter: fn (DiffStorageStoreRowInterface $row) => $this->formatMissingRow($row)
		);
	}

	/**
	 * Get all rows, that are present in this store, but not in the other and
	 * get all rows, that have a different value hash in the other store and
	 * get all rows, that are present in the other store, but not in this
	 *
	 * @param array<string, int|string> $arguments
	 * @return Traversable<DiffStorageStoreRow<TKeySpec, TValueSpec, TFullSpec, TFullSpec>>
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
	 * @return Traversable<DiffStorageStoreRow<TKeySpec, TValueSpec, TFullSpec, TFullSpec>>
	 */
	private function query(string $query, callable $stringFormatter): Traversable {
		$stmt = $this->pdo->query($query);
		yield from PDOTools::useStmt($stmt, function (PDOStatement $stmt) use ($stringFormatter) {
			$stmt->execute(['sA' => $this->storeA, 'sB' => $this->storeB]);
			while($row = $stmt->fetch(PDO::FETCH_NUM)) {
				/** @var array<int, string|null> $row */
				/** @var array<string, string|null> $d */
				$d = self::unserialize($row[1] ?? 'N;');
				/** @var array<string, string|null> $f */
				$f = self::unserialize($row[2] ?? 'N;');
				yield $this->instantiateRow($d, $f, $stringFormatter);
			}
		});
	}

	/**
	 * @return Traversable<int, array<string, mixed>>
	 */
	public function getIterator(): Traversable {
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
			while($dbRow = $stmt->fetch(PDO::FETCH_NUM)) {
				/** @var array<int, string|null> $dbRow */
				/** @var array<string, string|null> $row */
				$row = self::unserialize($dbRow[0] ?? 'N;');
				$row = $this->instantiateRow($row, [], function (DiffStorageStoreRowInterface $row) {
					$data = $row->getData();

					return $this->formatKeyValuePairs($data);
				});
				yield $row->getData();
			}
		});
	}

	/**
	 * @param array<string, mixed> $data
	 * @param null|array<string, string> $translation
	 * @return array<string, mixed>
	 */
	private function translate(array $data, ?array $translation = null): array {
		if($translation !== null) {
			$result = [];
			foreach($data as $key => $value) {
				if(array_key_exists($key, $translation)) {
					$key = $translation[$key];
				}

				if(is_object($value) && method_exists($value, '__toString')) {
					$value = (string) $value;
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
			/** @var string|null $result */
			$result = $stmt->fetch(PDO::FETCH_COLUMN, 0);

			return (int) $result;
		});
	}

	/**
	 * @param null|array<string, mixed> $localData
	 * @param null|array<string, mixed> $foreignData
	 * @param callable $stringFormatter
	 * @return DiffStorageStoreRow<TKeySpec, TValueSpec, TFullSpec, TFullSpec>
	 */
	private function instantiateRow(?array $localData, ?array $foreignData, callable $stringFormatter): DiffStorageStoreRow {
		// @phpstan-ignore-next-line
		return new DiffStorageStoreRow($localData, $foreignData, $this->keys, $this->valueKeys, $this->converter, $stringFormatter);
	}

	/**
	 * @param DiffStorageStoreRowInterface<TKeySpec, TValueSpec, TKeySpec, TValueSpec> $row
	 * @return string
	 */
	private function formatNewRow(DiffStorageStoreRowInterface $row): string {
		$keys = $this->formatKeyValuePairs($row->getLocal()->getKeyData(), false);
		$values = $this->formatKeyValuePairs($row->getLocal()->getValueData());

		return sprintf("New %s (%s)", $keys, $values);
	}

	/**
	 * @param DiffStorageStoreRowInterface<TKeySpec, TValueSpec, TKeySpec, TValueSpec> $row
	 * @return string
	 */
	private function formatUnchangedRow(DiffStorageStoreRowInterface $row): string {
		$keys = $this->formatKeyValuePairs($row->getLocal()->getKeyData(), false);

		return sprintf("Unchanged %s", $keys);
	}

	/**
	 * @param DiffStorageStoreRowInterface<TKeySpec, TValueSpec, TKeySpec, TValueSpec> $row
	 * @return string
	 */
	private function formatChangedRow(DiffStorageStoreRowInterface $row): string {
		$keys = $this->formatKeyValuePairs($row->getLocal()->getKeyData(), false);

		// @phpstan-ignore-next-line
		return sprintf("Changed %s => %s", $keys, $row->getDiffFormatted($this->valueKeys));
	}

	/**
	 * @param DiffStorageStoreRowInterface<TKeySpec, TValueSpec, TKeySpec, TValueSpec> $row
	 * @return string
	 */
	private function formatMissingRow(DiffStorageStoreRowInterface $row): string {
		$keys = $this->formatKeyValuePairs($row->getForeign()->getKeyData(), false);
		$values = $this->formatKeyValuePairs($row->getForeign()->getValueData());

		return sprintf("Missing %s (%s)", $keys, $values);
	}

	/**
	 * @param array<string, mixed> $keyValues
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
	 * @return array<string, mixed>
	 */
	private function buildMetaData(array $data): array {
		$metaData = $data;

		foreach($metaData as $key => $value) {
			if($value instanceof DateTimeInterface) {
				$metaData[$key] = $value->format('c');
			}
		}

		$metaData = array_diff_key($metaData, array_diff_key($metaData, $this->converter));
		$metaData['___data'] = serialize($data);
		$metaData['___sort'] = $this->counter;

		return $metaData;
	}

	/**
	 * @param string $data
	 * @return array<mixed, mixed>|null
	 */
	private static function unserialize(string $data): ?array {
		/** @var array<mixed, mixed>|false $result */
		$result = unserialize($data, ['allowed_classes' => true]);
		if($result === false) {
			return null;
		}

		return $result;
	}
}
