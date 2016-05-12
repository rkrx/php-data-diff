<?php
namespace DataDiff;

use Exception;

class DiffStorageStoreRow implements DiffStorageStoreRowInterface {
	/** @var array */
	private $data = [];
	/** @var DiffStorageStoreRowData */
	private $localData;
	/** @var DiffStorageStoreRowData */
	private $foreignRowData;
	/** @var callable */
	private $stringFormatter;

	/**
	 * @param array $localData
	 * @param array $foreignData
	 * @param array $keys
	 * @param array $valueKeys
	 * @param array $converter
	 * @param callable $stringFormatter
	 */
	public function __construct(array $localData = null, array $foreignData = null, array $keys, array $valueKeys, array $converter, $stringFormatter) {
		if($localData !== null) {
			$this->data = $localData;
		} elseif($foreignData !== null) {
			$this->data = $foreignData;
		}
		$localData = is_array($localData) ? $localData : [];
		$foreignData = is_array($foreignData) ? $foreignData : [];
		$this->localData = new DiffStorageStoreRowData($localData, $foreignData, $keys, $valueKeys, $converter);
		$this->foreignRowData = new DiffStorageStoreRowData($foreignData, $localData, $keys, $valueKeys, $converter);
		$this->stringFormatter = $stringFormatter;
	}

	/**
	 * @return DiffStorageStoreRowData
	 */
	public function getLocal() {
		return $this->localData;
	}

	/**
	 * @return DiffStorageStoreRowData
	 */
	public function getForeign() {
		return $this->foreignRowData;
	}

	/**
	 * `$options` are:
	 * * `keys`: Only these keys are considered and returned
	 * * `ignore`: These keys are ignored and omitted
	 *
	 * @param array $options
	 * @return array
	 */
	public function getData(array $options = []) {
		return $this->localData->getData($options);
	}

	/**
	 * `$options` are:
	 * * `keys`: Only these keys are considered and returned
	 * * `ignore`: These keys are ignored and omitted
	 *
	 * @param array $options
	 * @return array
	 */
	public function getForeignData(array $options = []) {
		return $this->foreignRowData->getData($options);
	}

	/**
	 * @param array $fields
	 * @return array
	 */
	public function getDiff(array $fields = null) {
		return $this->localData->getDiff($fields);
	}

	/**
	 * @param array $fields
	 * @param mixed $format
	 * @return array
	 * @throws Exception
	 */
	public function getDiffFormatted(array $fields = null, $format = null) {
		return $this->localData->getDiffFormatted($fields, $format);
	}

	/**
	 * @return mixed
	 */
	public function jsonSerialize() {
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

	/**
	 * @return string
	 */
	public function __toString() {
		return (string) call_user_func($this->stringFormatter, $this);
	}
}
