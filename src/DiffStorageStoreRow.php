<?php
namespace DataDiff;

use Exception;
use Generator;
use PDO;
use PDOStatement;

class DiffStorageStoreRow implements \JsonSerializable, \ArrayAccess {
	/** @var array */
	private $data;
	/** @var array */
	private $row;
	/** @var array */
	private $foreignRow;

	/**
	 * @param array $row
	 * @param array $foreignRow
	 */
	public function __construct(array $row = null, array $foreignRow = null) {
		$this->row = is_array($row) ? $row : [];
		$this->foreignRow = is_array($foreignRow) ? $foreignRow : [];
		if($row !== null) {
			$this->data = $row;
		} elseif($foreignRow !== null) {
			$this->data = $foreignRow;
		}
	}

	/**
	 * @return array
	 */
	public function getData() {
		return $this->row;
	}

	/**
	 * @return array
	 */
	public function getForeignData() {
		return $this->foreignRow;
	}

	/**
	 * @return array
	 */
	public function getDiff() {
		$diff = [];
		$diffFn = function ($keysA) use (&$diff) {
			foreach($keysA as $key) {
				if(!array_key_exists($key, $this->foreignRow)) {
					$diff[$key] = ['local' => $this->row[$key], 'foreign' => null];
				} elseif(!array_key_exists($key, $this->row)) {
					$diff[$key] = ['local' => null, 'foreign' => $this->foreignRow[$key]];
				} elseif(json_encode($this->row[$key]) !== json_encode($this->foreignRow[$key])) {
					$diff[$key] = ['local' => $this->row[$key], 'foreign' => $this->foreignRow[$key]];
				}
			}
		};
		$keysA = array_keys($this->row);
		$keysB = array_keys($this->foreignRow);
		$diffFn($keysA);
		$diffFn($keysB);
		return $diff;
	}

	/**
	 * @return mixed
	 */
	function jsonSerialize() {
		return $this->data;
	}

	/**
	 * @param mixed $offset
	 * @return boolean true on success or false on failure.
	 */
	public function offsetExists($offset) {
		return array_key_exists($offset, $this->data);
	}

	/**
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet($offset) {
		if($this->offsetExists($offset)) {
			return $this->data[$offset];
		}
		return null;
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 * @return void
	 */
	public function offsetSet($offset, $value) {
		$this->data[$offset] = $value;
	}

	/**
	 * @param mixed $offset
	 * @return void
	 */
	public function offsetUnset($offset) {
		if($this->offsetExists($offset)) {
			unset($this->data[$offset]);
		}
	}
}
