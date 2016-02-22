<?php
namespace DataDiff;

use Exception;
use Generator;
use const null;
use PDO;
use PDOStatement;

class DiffStorageStoreRow implements \JsonSerializable, \ArrayAccess {
	/** @var array */
	private $data;
	/** @var array */
	private $row;
	/** @var array */
	private $foreignRow;
	/** @var array */
	private $dataSchema;

	/**
	 * @param array $row
	 * @param array $foreignRow
	 * @param array $dataSchema
	 */
	public function __construct(array $row = null, array $foreignRow = null, array $dataSchema = null) {
		$this->dataSchema = $dataSchema;
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
	 * @param array $fields
	 * @return array
	 */
	public function getDiff(array $fields = null) {
		$diff = [];
		$diffFn = function ($keysA) use (&$diff, $fields) {
			foreach($keysA as $key) {
				if($fields !== null && !in_array($key, $fields)) {
					continue;
				}
				if(!array_key_exists($key, $this->foreignRow)) {
					$diff[$key] = ['local' => $this->row[$key], 'foreign' => null];
				} elseif(!array_key_exists($key, $this->row)) {
					$diff[$key] = ['local' => null, 'foreign' => $this->foreignRow[$key]];
				} else {
					$v1 = $this->row[$key];
					$v2 = $this->foreignRow[$key];
					if(array_key_exists($key, $this->dataSchema)) {
						$v1 = call_user_func($this->dataSchema[$key], $v1);
						$v2 = call_user_func($this->dataSchema[$key], $v2);
					}
					if(json_encode($v1) !== json_encode($v2)) {
						$diff[$key] = ['local' => $this->row[$key], 'foreign' => $this->foreignRow[$key]];
					}
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
	 * @param array $fields
	 * @param mixed $format
	 * @return array
	 * @throws Exception
	 */
	public function getDiffFormatted(array $fields = null, $format = null) {
		$diff = $this->getDiff($fields);
		if($format === null) {
			$result = [];
			foreach($diff as $fieldName => $values) {
				$result[] = sprintf("%s: %s -> %s", $fieldName, $values['foreign'], $values['local']);
			}
			return join(', ', $result);
		}
		throw new Exception("Unknown format: {$format}");
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
