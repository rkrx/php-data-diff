<?php
namespace DataDiff;

use DataDiff\Tools\Json;
use DataDiff\Tools\StringTools;
use RuntimeException;

class DiffStorageStoreRowData implements DiffStorageStoreRowDataInterface {
	/** @var array<string, mixed> */
	private $row;
	/** @var array<string, mixed> */
	private $foreignRow;
	/** @var string[] */
	private $keys;
	/** @var string[] */
	private $valueKeys;
	/** @var array<string, callable(mixed): (scalar|null)> */
	private $converter;

	/**
	 * @param array<string, mixed> $row
	 * @param array<string, mixed> $foreignRow
	 * @param string[] $keys
	 * @param string[] $valueKeys
	 * @param array<string, callable(mixed): (scalar|null)> $converter
	 */
	public function __construct(array $row, array $foreignRow, array $keys, array $valueKeys, array $converter) {
		$this->row = $row;
		$this->foreignRow = $foreignRow;
		$this->keys = $keys;
		$this->valueKeys = $valueKeys;
		$this->converter = $converter;
	}

	/**
	 * @inheritDoc
	 */
	public function getData(array $options = []): array {
		return $this->applyOptions($this->row, $options);
	}

	/**
	 * @inheritDoc
	 */
	public function getForeignData(array $options = []): array {
		return $this->applyOptions($this->foreignRow, $options);
	}

	/**
	 * @inheritDoc
	 */
	public function getKeyData(array $options = []): array {
		$row = $this->getData($options);
		$keys = (array) array_combine($this->keys, $this->keys);
		return array_intersect_key($row, $keys);
	}

	/**
	 * @inheritDoc
	 */
	public function getValueData(array $options = []): array {
		$row = $this->getData($options);
		$keys = (array) array_combine($this->valueKeys, $this->valueKeys);
		return array_intersect_key($row, $keys);
	}

	/**
	 * @inheritDoc
	 */
	public function getDiff(?array $fields = null): array {
		$diff = [];
		$localRow = $this->getData(['keys' => $fields]);
		$foreignRow = $this->getForeignData(['keys' => $fields]);
		$keys = array_keys(
			array_merge(
				(array) array_combine($this->valueKeys, array_fill(0, count($this->valueKeys), null)),
				$this->row,
				$this->foreignRow
			)
		);
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

		$asString = static function ($value) {
			if(is_float($value)) {
				$value = number_format($value, 8, '.', '');
			} elseif(is_scalar($value)) {
				$value = (string) $value;
			}
			return serialize($value);
		};

		foreach($keys as $key) {
			$localValue = $conv($formattedLocalRow, $key);
			$foreignValue = $conv($formattedForeignRow, $key);

			if($asString($localValue) !== $asString($foreignValue)) {
				$diff[(string) $key] = ['local' => $localValue, 'foreign' => $foreignValue];
			}
		}

		return $diff;
	}

	/**
	 * @inheritDoc
	 */
	public function getDiffFormatted(?array $fields = null, ?string $format = null): string {
		$diff = $this->getDiff($fields);
		if($format === null) {
			$result = [];
			$formatVal = static function ($value) {
				if(is_string($value)) {
					$value = StringTools::shorten($value);
				}
				return Json::encode($value);
			};
			foreach($diff as $fieldName => $values) {
				$foreignValue = $formatVal($values['foreign']);
				$localValue = $formatVal($values['local']);
				$result[] = sprintf("%s: %s -> %s", $fieldName, $foreignValue, $localValue);
			}
			return implode(', ', $result);
		}
		throw new RuntimeException("Unknown format: {$format}");
	}

	/**
	 * @param array<string, mixed> $row
	 * @param array<string, mixed> $options
	 * @return array<string, mixed>
	 */
	private function applyOptions(array $row, array $options): array {
		if(count($options) < 1) {
			return $row;
		}
		if(array_key_exists('keys', $options) && is_array($options['keys'])) {
			$keys = (array) array_combine($options['keys'], $options['keys']);
			$row = array_intersect_key($row, $keys);
		}
		if(array_key_exists('ignore', $options) && is_array($options['ignore'])) {
			$keys = (array) array_combine($options['ignore'], $options['ignore']);
			$row = array_diff_key($row, $keys);
		}
		if(array_key_exists('only-differences', $options) && $options['only-differences']) {
			$diffFields = $this->getDiff();
			$row = array_intersect_key($row, $diffFields);
		}
		if(array_key_exists('only-schema-fields', $options) && $options['only-schema-fields']) {
			$keys = (array) array_combine($this->keys, $this->keys);
			$valueKeys = (array) array_combine($this->valueKeys, $this->valueKeys);
			$keys = array_merge($keys, $valueKeys);
			$row = array_intersect_key($row, $keys);
		}
		return $row;
	}

	/**
	 * @param array<string, mixed> $row
	 * @return array<string, null|string>
	 */
	private function formatRow(array $row): array {
		$schema = $this->converter;
		$schema = array_map(static function () { return null; }, $schema);
		return array_merge($schema, $row);
	}
}
