<?php
namespace DataDiff;

use DataDiff\Tools\StringTools;
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
		$row = $this->getData($options);
		$row = array_intersect_key($row, array_combine($this->keys, $this->keys));
		return $row;
	}

	/**
	 * @param array $options
	 * @return array
	 */
	public function getValueData(array $options = []) {
		$row = $this->getData($options);
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
		$keys = array_keys(array_merge(
			array_combine($this->valueKeys, array_fill(0, count($this->valueKeys), null)),
			$this->row,
			$this->foreignRow
		));
		$formattedLocalRow = $this->formatRow($localRow);
		$formattedForeignRow = $this->formatRow($foreignRow);
		
		$conv = function (array $row, $key) {
			$value = null;
			if(array_key_exists($key, $row)) {
				$value = $row[$key];
				if(array_key_exists($key, $this->converter) && $value !== null) {
					$value = call_user_func($this->converter[$key], $value);
				}
			}
			return $value;
		};
		
		$asString = function ($value) {
			if(is_float($value)) {
				$value = number_format($value, 8, '.', '');
			} elseif(is_scalar($value)) {
				$value = (string) $value;
			}
			return serialize($value);
		};
		
		foreach($keys as $key) {
			$localValue = call_user_func($conv, $formattedLocalRow, $key);
			$foreignValue = call_user_func($conv, $formattedForeignRow, $key);
			
			if($asString($localValue) !== $asString($foreignValue)) {
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
			$formatVal = function ($value) {
				if(is_string($value)) {
					$value = StringTools::shorten($value);
				}
				return StringTools::jsonEncode($value);
			};
			foreach($diff as $fieldName => $values) {
				$foreignValue = $formatVal($values['foreign']);
				$localValue = $formatVal($values['local']);
				$result[] = sprintf("%s: %s -> %s", $fieldName, $foreignValue, $localValue);
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
		if(array_key_exists('only-differences', $options) && $options['only-differences']) {
			$diffFields = $this->getDiff();
			$row = array_intersect_key($row, $diffFields);
		}
		if(array_key_exists('only-schema-fields', $options) && $options['only-schema-fields']) {
			$keys = array_combine($this->keys, $this->keys);
			$valueKeys = array_combine($this->valueKeys, $this->valueKeys);
			$keys = array_merge($keys, $valueKeys);
			$row = array_intersect_key($row, $keys);
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
