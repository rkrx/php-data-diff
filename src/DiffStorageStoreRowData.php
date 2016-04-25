<?php
namespace DataDiff;

use Exception;

class DiffStorageStoreRowData implements DiffStorageStoreRowDataInterface {
	/** @var array */
	private $row;
	/** @var array */
	private $keys;
	/** @var array */
	private $valueKeys;
	/** @var array */
	private $converter;
	/** @var array */
	private $foreignRow;

	/**
	 * @param array $row
	 * @param array $foreignRow
	 * @param array $keys
	 * @param array $valueKeys
	 * @param array $converter
	 */
	public function __construct(array $row = [], array $foreignRow = [], array $keys, array $valueKeys, array $converter) {
		$this->row = $row;
		$this->foreignRow = $foreignRow;
		$this->keys = $keys;
		$this->valueKeys = $valueKeys;
		$this->converter = $converter;
	}

	/**
	 * @param array $options
	 * @return array
	 */
	public function getData(array $options = []) {
		return $this->applyOptions($this->row, $options);
	}

	/**
	 * @param array $options
	 * @return array
	 */
	public function getForeignData(array $options = []) {
		return $this->applyOptions($this->foreignRow, $options);
	}

	/**
	 * @param array $options
	 * @return array
	 */
	public function getKeyData(array $options = []) {
		$row = $this->getData();
		$row = array_intersect_key($row, array_combine($this->keys, $this->keys));
		return $row;
	}

	/**
	 * @param array $options
	 * @return array
	 */
	public function getValueData(array $options = []) {
		$row = $this->getData();
		$row = array_intersect_key($row, array_combine($this->valueKeys, $this->valueKeys));
		return $row;
	}

	/**
	 * @param array $fields
	 * @return array
	 */
	public function getDiff(array $fields = null) {
		$diff = [];
		$localRow = $this->getData(['keys' => $fields]);
		$foreignRow = $this->getForeignData(['keys' => $fields]);
		$keys = array_keys(array_merge($this->row, $this->foreignRow));
		$formattedLocalRow = $this->formatRow($localRow);
		$formattedForeignRow = $this->formatRow($foreignRow);
		foreach($keys as $key) {
			$conv = function (array $row) use ($key) {
				$value = null;
				if(array_key_exists($key, $row)) {
					$value = $row[$key];
					if(array_key_exists($key, $this->converter)) {
						$value = call_user_func($this->converter[$key], $value);
					}
				}
				return $value;
			};
			$localValue = call_user_func($conv, $formattedLocalRow);
			$foreignValue = call_user_func($conv, $formattedForeignRow);
			if(json_encode($localValue) !== json_encode($foreignValue)) {
				$diff[$key] = ['local' => $localValue, 'foreign' => $foreignValue];
			}
		}
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
	 * @param array $row
	 * @param array $options
	 * @return array
	 */
	private function applyOptions(array $row, array $options) {
		if(count($options) < 1) {
			return $row;
		}
		if(array_key_exists('keys', $options) && is_array($options['keys'])) {
			$row = array_intersect_key($row, array_combine($options['keys'], $options['keys']));
		}
		if(array_key_exists('ignore', $options) && is_array($options['ignore'])) {
			$row = array_diff_key($row, array_combine($options['ignore'], $options['ignore']));
		}
		return $row;
	}

	/**
	 * @param array $row
	 * @return array
	 */
	private function formatRow($row) {
		$schema = $this->converter;
		$schema = array_map(function () { return null; }, $schema);
		$row = array_merge($schema, $row);
		return $row;
	}
}
