<?php
namespace DataDiff;

use Exception;
use PDO;

/**
 * @package DataDiff
 */
abstract class DiffStorage {
	/** @var PDO */
	private $pdo = null;
	/** @var DiffStorageStore */
	private $storeA = null;
	/** @var DiffStorageStore */
	private $storeB = null;
	/** @var array */
	private $keys;

	/**
	 * Predefined types:
	 *     - integer
	 *     - string
	 *     - bool
	 *     - float
	 *     - double
	 *     - money
	 *
	 * @param array $keySchema
	 * @param array $valueSchema
	 * @param callable|null $duplicateKeyHandler
	 * @param array $options
	 */
	public function __construct(array $keySchema, array $valueSchema, $duplicateKeyHandler = null, array $options) {
		if(!array_key_exists('dsn', $options)) {
			$options['dsn'] = 'sqlite::memory:';
		}
		$this->pdo = new PDO($options['dsn'], null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
		$this->pdo->exec("PRAGMA synchronous=OFF");
		$this->pdo->exec("PRAGMA count_changes=OFF");
		$this->pdo->exec("PRAGMA journal_mode=MEMORY");
		$this->pdo->exec("PRAGMA temp_store=MEMORY");
		$this->compatibility();

		$this->pdo->exec('CREATE TABLE data_store (s_ab TEXT, s_key TEXT, s_value TEXT, s_data TEXT, s_sort INT, PRIMARY KEY(s_ab, s_key))');
		$this->pdo->exec('CREATE INDEX data_store_ab_index ON data_store (s_ab, s_key)');
		$this->pdo->exec('CREATE INDEX data_store_key_index ON data_store (s_key)');

		$sqlKeySchema = $this->buildSchema($keySchema);
		$sqlValueSchema = $this->buildSchema($valueSchema);

		$keyConverter = $this->buildConverter($keySchema);
		$valueConverter = $this->buildConverter($valueSchema);
		$converter = array_merge($keyConverter, $valueConverter);

		if($duplicateKeyHandler === null) {
			$duplicateKeyHandler = function (array $newData = null, array $oldData = null) {
				return array_merge($oldData, $newData);
			};
		}

		$this->storeA = new DiffStorageStore($this->pdo, $sqlKeySchema, $sqlValueSchema, $converter, 'a', 'b', $duplicateKeyHandler);
		$this->storeB = new DiffStorageStore($this->pdo, $sqlKeySchema, $sqlValueSchema, $converter, 'b', 'a', $duplicateKeyHandler);
	}

	/**
	 * @return array
	 */
	public function getKeys() {
		return $this->keys;
	}

	/**
	 * @return DiffStorageStore
	 */
	public function storeA() {
		return $this->storeA;
	}

	/**
	 * @return DiffStorageStore
	 */
	public function storeB() {
		return $this->storeB;
	}

	/**
	 * @param array $schema
	 * @return array
	 * @throws Exception
	 */
	private function buildSchema($schema) {
		$def = [];
		foreach($schema as $name => $type) {
			switch ($type) {
				case 'BOOL':
					$def[] = sprintf('CASE WHEN CAST(:'.$name.' AS INT) = 0 THEN \'false\' ELSE \'true\' END');
					break;
				case 'INT':
					$def[] = 'printf("%d", :'.$name.')';
					break;
				case 'FLOAT':
					$def[] = 'printf("%0.6f", :'.$name.')';
					break;
				case 'MONEY':
					$def[] = 'printf("%0.2f", :'.$name.')';
					break;
				case 'STRING':
					$def[] = '\'"\'||HEX(TRIM(:'.$name.'))||\'"\'';
					break;
				case 'MD5':
					$def[] = '\'"\'||md5(:'.$name.')||\'"\'';
					break;
			}
		}
		return join('||"|"||', $def);
	}

	/**
	 * @param array $schema
	 * @return array
	 * @throws Exception
	 */
	private function buildConverter($schema) {
		$def = [];
		foreach($schema as $name => $type) {
			switch ($type) {
				case 'BOOL':
					$def[$name] = 'boolval';
					break;
				case 'INT':
					$def[$name] = 'intval';
					break;
				case 'FLOAT':
					$def[$name] = function ($value) { return number_format($value, 6, '.', ''); };
					break;
				case 'MONEY':
					$def[$name] = function ($value) { return number_format($value, 2, '.', ''); };
					break;
				case 'STRING':
					$def[$name] = function ($value) { return (string) $value; };
					break;
				case 'MD5':
					$def[$name] = function ($value) { return md5((string) $value); };
					break;
			}
		}
		return $def;
	}

	/**
	 */
	private function compatibility() {
		if(!$this->testStatement('SELECT printf("%0.2f", 19.99999) AS res')) {
			$this->registerUDFunction('printf', function ($fmt, $arg) {
				return sprintf($fmt, $arg);
			});
		}

		if(!$this->testStatement('SELECT md5("aaa") AS md5res')) {
			$this->registerUDFunction('md5', function ($arg) {
				return md5($arg);
			});
		}
	}

	/**
	 * @param string $query
	 * @return bool
	 */
	private function testStatement($query) {
		try {
			return $this->pdo->query($query)->execute() !== false;
		} catch (\PDOException $e) {
			return false;
		}
	}

	/**
	 * @param string $name
	 * @param mixed $callback
	 * @throws Exception
	 */
	private function registerUDFunction($name, $callback) {
		if(!method_exists($this->pdo, 'sqliteCreateFunction')) {
			throw new Exception('It is not possible to create user defined functions for rkr/data-diff\'s sqlite instance');
		}
		call_user_func([$this->pdo, 'sqliteCreateFunction'], $name, $callback);
	}
}
