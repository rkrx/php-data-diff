<?php
namespace DataDiff;

class DiffStorageStoreRow implements DiffStorageStoreRowInterface {
	/** @var array<string, mixed> */
	private $data = [];
	/** @var DiffStorageStoreRowData */
	private $localData;
	/** @var DiffStorageStoreRowData */
	private $foreignRowData;
	/** @var callable(DiffStorageStoreRow): string */
	private $stringFormatter;

	/**
	 * @param null|array<string, mixed> $localData
	 * @param null|array<string, mixed> $foreignData
	 * @param string[] $keys
	 * @param string[] $valueKeys
	 * @param array<string, callable(mixed): (scalar|null)> $converter
	 * @param callable(DiffStorageStoreRow): string $stringFormatter
	 */
	public function __construct(?array $localData, ?array $foreignData, array $keys, array $valueKeys, array $converter, callable $stringFormatter) {
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
	 * @param array<string, mixed> $options
	 * @return array<string, mixed>
	 */
	public function getData(array $options = []): array {
		return $this->localData->getData($options);
	}

	/**
	 * `$options` are:
	 * * `keys`: Only these keys are considered and returned
	 * * `ignore`: These keys are ignored and omitted
	 *
	 * @param array<string, mixed> $options
	 * @return array<string, mixed>
	 */
	public function getForeignData(array $options = []): array {
		return $this->foreignRowData->getData($options);
	}

	/**
	 * @param null|string[] $fields
	 * @return array<string, array{local: mixed, foreign: mixed}>
	 */
	public function getDiff(?array $fields = null): array {
		return $this->localData->getDiff($fields);
	}

	/**
	 * @param null|array<string, mixed> $fields
	 * @param null|string $format
	 * @return string
	 */
	public function getDiffFormatted(?array $fields = null, ?string $format = null): string {
		return $this->localData->getDiffFormatted($fields, $format);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function jsonSerialize() {
		return $this->data;
	}

	/**
	 * @param string $offset
	 * @return bool true on success or false on failure.
	 */
	public function offsetExists($offset): bool {
		return array_key_exists($offset, $this->data);
	}

	/**
	 * @param string $offset
	 * @return mixed
	 */
	public function offsetGet($offset) {
		if($this->offsetExists($offset)) {
			return $this->data[$offset];
		}
		return null;
	}

	/**
	 * @param string $offset
	 * @param mixed $value
	 * @return void
	 */
	public function offsetSet($offset, $value): void {
		$this->data[$offset] = $value;
	}

	/**
	 * @param string $offset
	 * @return void
	 */
	public function offsetUnset($offset): void {
		if($this->offsetExists($offset)) {
			unset($this->data[$offset]);
		}
	}

	/**
	 * @return string
	 */
	public function __toString(): string {
		$result = call_user_func($this->stringFormatter, $this);
		/** @var string $result */
		return (string) $result;
	}
}
