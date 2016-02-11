<?php
namespace DataDiff;

use Exception;
use Generator;
use PDO;
use PDOStatement;

class DiffStorageStoreRow implements \JsonSerializable, \ArrayAccess {
	/** @var array */
	private $row;
	/** @var array */
	private $foreignRow;

	/**
	 * @param array $row
	 * @param array $foreignRow
	 */
	public function __construct(array $row, array $foreignRow = null) {
		$this->row = $row;
		$this->foreignRow = $foreignRow;
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
	public function getDiff() {
		return $this->foreignRow;
	}

	/**
	 * @return mixed
	 */
	function jsonSerialize() {
		return $this->row;
	}

	/**
	 * @param mixed $offset
	 * @return boolean true on success or false on failure.
	 */
	public function offsetExists($offset) {
		return array_key_exists($offset, $this->row);
	}

	/**
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet($offset) {
		if($this->offsetExists($offset)) {
			return $this->row[$offset];
		}
		return null;
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 * @return void
	 */
	public function offsetSet($offset, $value) {
		$this->row[$offset] = $value;
	}

	/**
	 * @param mixed $offset
	 * @return void
	 */
	public function offsetUnset($offset) {
		if($this->offsetExists($offset)) {
			unset($this->row[$offset]);
		}
	}
}
