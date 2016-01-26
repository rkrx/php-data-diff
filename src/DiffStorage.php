<?php
namespace DataDiff;

use PDO;
use Traversable;

class DiffStorage {
	/** @var PDO */
	private $pdo = null;

	/**
	 */
	public function __construct() {
		$this->pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
		$this->pdo->exec('CREATE TABLE data_store (s_ab TEXT, s_key TEXT, s_value TEXT, PRIMARY KEY(s_ab, s_key))');
		$this->insertStmt = $this->pdo->prepare('INSERT INTO data_store (s_ab, s_key, s_value) VALUES (:ab, :k, :v)');
	}

	/**
	 * @param mixed $key
	 * @param mixed $value
	 * @return $this
	 */
	public function storeA($key, $value = null) {
		if(func_num_args() === 1) {
			$value = $key;
		}
		return $this->store('a', $key, $value);
	}

	/**
	 * @param mixed $key
	 * @param mixed $value
	 * @return $this
	 */
	public function storeB($key, $value = null) {
		if(func_num_args() === 1) {
			$value = $key;
		}
		return $this->store('b', $key, $value);
	}

	/**
	 * @return Traversable
	 */
	public function getNewA() {
		$stmt = $this->pdo->query('
			SELECT
				a.s_key,
				a.s_value
			FROM
				data_store AS a
			LEFT JOIN
				data_store AS b ON b.s_ab = "b" AND a.s_key = b.s_key
			WHERE
				a.s_ab = "a"
				AND
				b.s_ab IS NULL
		');
		$stmt->execute();
		while($row = $stmt->fetch(PDO::FETCH_NUM)) {
			$k = json_decode($row[0], true);
			$v = json_decode($row[1], true);
			yield $k => $v;
		}
	}

	/**
	 * @return Traversable
	 */
	public function getChangedA() {
		$stmt = $this->pdo->query('
			SELECT
				a.s_key,
				a.s_value
			FROM
				data_store AS a
			INNER JOIN
				data_store AS b ON b.s_ab = "b" AND a.s_key = b.s_key
			WHERE
				a.s_ab = "a"
				AND
				a.s_value != b.s_value
		');
		$stmt->execute();
		while($row = $stmt->fetch(PDO::FETCH_NUM)) {
			$k = json_decode($row[0], true);
			$v = json_decode($row[1], true);
			yield $k => $v;
		}
	}

	/**
	 * @return Traversable
	 */
	public function getNewOrChanged() {
		foreach($this->getNewA() as $key => $value) {
			yield $key => $value;
		}
		foreach($this->getChangedA() as $key => $value) {
			yield $key => $value;
		}
	}

	/**
	 * @return Traversable
	 */
	public function getRemovedB() {
		$stmt = $this->pdo->query('
			SELECT
				b.s_key,
				b.s_value
			FROM
				data_store AS b
			LEFT JOIN
				data_store AS a ON a.s_ab = "a" AND a.s_key = b.s_key
			WHERE
				b.s_ab = "b"
				AND
				a.s_ab IS NULL
		');
		$stmt->execute();
		while($row = $stmt->fetch(PDO::FETCH_NUM)) {
			$k = json_decode($row[0], true);
			$v = json_decode($row[1], true);
			yield $k => $v;
		}
	}

	/**
	 * @return $this
	 */
	public function clearAll() {
		$this->pdo->exec('DELETE FROM data_store;');
	}

	/**
	 * @param string $store
	 * @param mixed $key
	 * @param mixed $val
	 * @return $this
	 */
	private function store($store, $key, $val) {
		$key = json_decode(json_encode($key, JSON_UNESCAPED_SLASHES), true);
		$val = json_decode(json_encode($val, JSON_UNESCAPED_SLASHES), true);

		$key = $this->recursiveKeySort($key);
		$val = $this->recursiveKeySort($val);

		$sKey = json_encode($key, JSON_UNESCAPED_SLASHES);
		$sValue = json_encode($val, JSON_UNESCAPED_SLASHES);

		$this->insertStmt->execute(['ab' => $store, 'k' => $sKey, 'v' => $sValue]);
	}

	/**
	 * @param mixed $val
	 * @return mixed
	 */
	private function recursiveKeySort($val) {
		if(is_array($val)) {
			ksort($val);
			foreach($val as &$v) {
				$v = $this->recursiveKeySort($v);
			}
		}
		return $val;
	}
}
