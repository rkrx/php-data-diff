<?php

namespace DataDiff;

use DataDiff\Exceptions\EmptySchemaException;
use DataDiff\Exceptions\InvalidSchemaException;
use DataDiff\Tools\PDOTools;
use DateTimeInterface;
use Exception;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

/**
 * @template TKeySpec of array<string, mixed>
 * @template TValueSpec of array<string, mixed>
 * @template TExtraSpec of array<string, mixed>
 *
 * @phpstan-type TKeysOfKeySpec key-of<TKeySpec>
 *
 * @implements DiffStorageInterface<TKeySpec, TValueSpec, TExtraSpec>
 */
abstract class DiffStorage implements DiffStorageInterface, DiffStorageFieldTypeConstants {
	private PDO $pdo;
	/** @var DiffStorageStore<TKeySpec, TValueSpec&TExtraSpec, TKeySpec&TValueSpec&TExtraSpec> */
	private DiffStorageStore $storeA;
	/** @var DiffStorageStore<TKeySpec, TValueSpec&TExtraSpec, TKeySpec&TValueSpec&TExtraSpec> */
	private DiffStorageStore $storeB;
	/** @var TKeysOfKeySpec[] */
	private array $keys;

	/**
	 * @param array<key-of<TKeySpec>, string> $keySchema
	 * @param array<key-of<TValueSpec>, string> $valueSchema
	 * @param array<string, mixed> $options
	 *
	 * @throws EmptySchemaException
	 * @throws InvalidSchemaException
	 */
	public function __construct(array $keySchema, array $valueSchema, array $options) {
		$options = $this->defineOptionDefaults($options);
		$dsn = $options['dsn'] ?? null;
		$dsn = is_string($dsn) ? $dsn : 'sqlite::memory:';
		$this->pdo = new PDO($dsn, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
		$this->initSqlite();
		$this->compatibility();
		$this->buildTables();

		$this->keys = array_keys($keySchema);
		$valueKeys = array_keys($valueSchema);

		$sqlKeySchema = $this->buildSchema($keySchema);
		$sqlValueSchema = $this->buildSchema($valueSchema);

		$keyConverter = $this->buildConverter($keySchema);
		$valueConverter = $this->buildConverter($valueSchema);

		$converter = array_merge($keyConverter, $valueConverter);

		$duplicateKeyHandler = $options['duplicate_key_handler'] ?? null;
		$duplicateKeyHandler = is_callable($duplicateKeyHandler) ? $duplicateKeyHandler : null;

		// @phpstan-ignore-next-line
		$this->storeA = new DiffStorageStore(
			pdo: $this->pdo,
			keySchema: $sqlKeySchema,
			valueSchema: $sqlValueSchema,
			keys: $this->keys,
			valueKeys: $valueKeys,
			converter: $converter,
			storeA: 'a',
			storeB: 'b',
			duplicateKeyHandler: $duplicateKeyHandler
		);

		// @phpstan-ignore-next-line
		$this->storeB = new DiffStorageStore(
			pdo: $this->pdo,
			keySchema: $sqlKeySchema,
			valueSchema: $sqlValueSchema,
			keys: $this->keys,
			valueKeys: $valueKeys,
			converter: $converter,
			storeA: 'b',
			storeB: 'a',
			duplicateKeyHandler: $duplicateKeyHandler
		);
	}

	/**
	 * @return TKeysOfKeySpec[]
	 */
	public function getKeys(): array {
		return $this->keys;
	}

	/**
	 * @return DiffStorageStore<TKeySpec, TValueSpec&TExtraSpec, TKeySpec&TValueSpec&TExtraSpec>
	 */
	public function storeA(): DiffStorageStore {
		return $this->storeA;
	}

	/**
	 * @return DiffStorageStore<TKeySpec, TValueSpec&TExtraSpec, TKeySpec&TValueSpec&TExtraSpec>
	 */
	public function storeB(): DiffStorageStore {
		return $this->storeB;
	}

	/**
	 * @param array<string, string> $schema
	 * @return string
	 * @throws EmptySchemaException
	 * @throws InvalidSchemaException
	 */
	private function buildSchema(array $schema): string {
		$def = [];
		foreach($schema as $name => $type) {
			switch($type) {
				case 'BOOL':
				case 'BOOLEAN':
					$def[] = sprintf('CASE WHEN CAST(:' . $name . ' AS INT) = 0 THEN \'false\' ELSE \'true\' END');
					break;
				case 'INT':
				case 'INTEGER':
					$def[] = 'printf("%d", :' . $name . ')';
					break;
				case 'FLOAT':
					$def[] = 'printf("%0.6f", :' . $name . ')';
					break;
				case 'DOUBLE':
					$def[] = 'printf("%0.12f", :' . $name . ')';
					break;
				case 'MONEY':
					$def[] = 'printf("%0.2f", :' . $name . ')';
					break;
				case 'STR':
				case 'STRING':
					$def[] = '\'"\'||HEX(TRIM(:' . $name . '))||\'"\'';
					break;
				case 'MD5':
					$def[] = '\'"\'||md5(:' . $name . ')||\'"\'';
					break;
				default:
					throw new InvalidSchemaException("Invalid type: {$type}");
			}
		}
		if(!count($def)) {
			throw new EmptySchemaException('Can\'t operate with empty schema');
		}

		return implode('||"|"||', $def);
	}

	/**
	 * @param array<string, string> $schema
	 * @return array<string, callable(mixed): (scalar|null)>
	 * @throws InvalidSchemaException
	 */
	private function buildConverter(array $schema): array {
		$def = [];
		foreach($schema as $name => $type) {
			$def[$name] = match ($type) {
				'BOOL', 'BOOLEAN' => static fn($value) => is_scalar($value) ? (bool) $value : null,
				'INT', 'INTEGER' => static fn($value) => is_scalar($value) ? (int) $value : null,
				'FLOAT' => static fn($value) => is_scalar($value) ? (float) number_format((float) $value, 6, '.', '') : null,
				'DOUBLE' => static fn($value) => is_scalar($value) ? (float) number_format((float) $value, 12, '.', '') : null,
				'MONEY' => static fn($value) => is_scalar($value) ? number_format((float) $value, 2, '.', '') : null,
				'STR', 'STRING' => static function ($value) {
					if($value instanceof DateTimeInterface) {
						return $value->format('c');
					}

					return is_scalar($value) ? (string) $value : null;
				},
				'MD5' => static fn($value) => is_scalar($value) ? md5((string) $value) : null,
				default => throw new InvalidSchemaException("Invalid type: {$type}")
			};
		}

		return $def;
	}

	/**
	 * @throws RuntimeException
	 */
	private function compatibility(): void {
		try {
			if(!$this->testStatement('SELECT printf("%0.2f", 19.99999) AS res')) {
				$this->registerUDFunction('printf', 'sprintf');
			}

			if(!$this->testStatement('SELECT md5("aaa") AS md5res')) {
				$this->registerUDFunction('md5', 'md5');
			}
		} catch(Exception $e) {
			$code = $e->getCode();
			throw new RuntimeException($e->getMessage(), is_int($code) ? $code : 0, $e);
		}
	}

	/**
	 * @param string $query
	 * @return bool
	 */
	private function testStatement(string $query): bool {
		try {
			$stmt = $this->pdo->query($query);

			return PDOTools::useStmt($stmt, static fn(PDOStatement $stmt) => $stmt->execute() !== false);
		} catch(PDOException) {
			return false;
		}
	}

	/**
	 * @param string $name
	 * @param mixed $callback
	 * @throws Exception
	 */
	private function registerUDFunction(string $name, $callback): void {
		// @phpstan-ignore-next-line
		if(!method_exists($this->pdo, 'sqliteCreateFunction')) {
			throw new Exception('It is not possible to create user defined functions for rkr/data-diff\'s sqlite instance');
		}
		// @phpstan-ignore-next-line
		call_user_func([$this->pdo, 'sqliteCreateFunction'], $name, $callback);
	}

	/**
	 */
	private function initSqlite(): void {
		$tryThis = function ($query) {
			try {
				if(!is_string($query)) {
					throw new Exception('Query is not a string');
				}
				$this->pdo->exec($query);
			} catch(Exception) {
				// If the execution failed, go on anyways
			}
		};
		$tryThis("PRAGMA synchronous=OFF");
		$tryThis("PRAGMA count_changes=OFF");
		$tryThis("PRAGMA journal_mode=MEMORY");
		$tryThis("PRAGMA temp_store=MEMORY");
	}

	/**
	 */
	private function buildTables(): void {
		$this->pdo->exec('CREATE TABLE data_store (s_ab TEXT, s_key TEXT, s_value TEXT, s_data TEXT, s_sort INT, PRIMARY KEY(s_ab, s_key))');
		$this->pdo->exec('CREATE INDEX data_store_ab_index ON data_store (s_ab, s_key)');
		$this->pdo->exec('CREATE INDEX data_store_key_index ON data_store (s_key)');
	}

	/**
	 * @param array<string, mixed> $options
	 * @return array<string, mixed>
	 */
	private function defineOptionDefaults(array $options): array {
		if(!array_key_exists('dsn', $options)) {
			$options['dsn'] = 'sqlite::memory:';
		}
		if(!array_key_exists('duplicate_key_handler', $options)) {
			$options['duplicate_key_handler'] = null;
		}

		return $options;
	}
}
