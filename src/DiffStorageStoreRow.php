<?php

namespace DataDiff;

/**
 * @template TKeySpec of array<string, mixed>
 * @template TValueSpec of array<string, mixed>
 * @template TLocal of array<string, mixed>
 * @template TForeign of array<string, mixed>
 *
 * @phpstan-type TLocalAndForeign TLocal|TForeign
 *
 * @phpstan-import-type TConverter from DiffStorageStoreRowDataInterface
 * @phpstan-import-type TStringFormatterFn from DiffStorageStoreRowDataInterface
 *
 * @implements DiffStorageStoreRowInterface<TKeySpec, TValueSpec, TLocal, TForeign>
 */
class DiffStorageStoreRow implements DiffStorageStoreRowInterface {
	/** @var TLocalAndForeign */
	private array $data = [];
	/** @var DiffStorageStoreRowData<TKeySpec, TValueSpec, TLocal, TForeign> */
	private DiffStorageStoreRowData $localData;
	/** @var DiffStorageStoreRowData<TKeySpec, TValueSpec, TForeign, TLocal> */
	private DiffStorageStoreRowData $foreignRowData;
	/** @var TStringFormatterFn */
	private $stringFormatter;

	/**
	 * @param TLocal $localData
	 * @param TForeign $foreignData
	 * @param string[] $keys
	 * @param string[] $valueKeys
	 * @param array<string, TConverter> $converter
	 * @param TStringFormatterFn $stringFormatter
	 */
	public function __construct(?array $localData, ?array $foreignData, array $keys, array $valueKeys, array $converter, callable $stringFormatter) {
		if($localData !== null) {
			$this->data = $localData;
		} elseif($foreignData !== null) {
			$this->data = $foreignData;
		}

		$localData = is_array($localData) ? $localData : [];
		$foreignData = is_array($foreignData) ? $foreignData : [];

		/** @var DiffStorageStoreRowData<TKeySpec, TValueSpec, TLocal, TForeign> $localRowData */
		$localRowData = new DiffStorageStoreRowData($localData, $foreignData, $keys, $valueKeys, $converter);
		$this->localData = $localRowData;

		/** @var DiffStorageStoreRowData<TKeySpec, TValueSpec, TForeign, TLocal> $foreignRowData */
		$foreignRowData = new DiffStorageStoreRowData($foreignData, $localData, $keys, $valueKeys, $converter);
		$this->foreignRowData = $foreignRowData;

		// @phpstan-ignore-next-line
		$this->stringFormatter = $stringFormatter;
	}

	/**
	 * @return DiffStorageStoreRowData<TKeySpec, TValueSpec, TLocal, TForeign>
	 */
	public function getLocal(): DiffStorageStoreRowData {
		return $this->localData;
	}

	/**
	 * @return DiffStorageStoreRowData<TKeySpec, TValueSpec, TForeign, TLocal>
	 */
	public function getForeign(): DiffStorageStoreRowData {
		return $this->foreignRowData;
	}

	/**
	 * `$options` are:
	 * * `keys`: Only these keys are considered and returned
	 * * `ignore`: These keys are ignored and omitted
	 *
	 * @param array<string, mixed> $options
	 * @return TLocal
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
	 * @return TForeign
	 */
	public function getForeignData(array $options = []): array {
		return $this->foreignRowData->getData($options);
	}

	/**
	 * @param null|list<key-of<TLocal>> $fields
	 * @return array<string, array{local: TLocal, foreign: TForeign}>
	 */
	public function getDiff(?array $fields = null): array {
		return $this->localData->getDiff($fields);
	}

	/**
	 * @param null|list<key-of<TLocal>> $fields
	 * @param null|string $format
	 * @return string
	 */
	public function getDiffFormatted(?array $fields = null, ?string $format = null): string {
		return $this->localData->getDiffFormatted($fields, $format);
	}

	/**
	 * @return TLocalAndForeign
	 */
	public function jsonSerialize() {
		return $this->data;
	}

	/**
	 * @param key-of<TLocalAndForeign> $offset
	 * @return bool true on success or false on failure.
	 */
	public function offsetExists($offset): bool {
		return array_key_exists($offset, $this->data);
	}

	/**
	 * @param key-of<TLocalAndForeign> $offset
	 * @return value-of<TLocalAndForeign>
	 */
	public function offsetGet($offset) {
		if($this->offsetExists($offset)) {
			return $this->data[$offset];
		}

		return null;
	}

	/**
	 * @param key-of<TLocalAndForeign> $offset
	 * @param value-of<TLocalAndForeign> $value
	 * @return void
	 */
	public function offsetSet($offset, $value): void {
		// @phpstan-ignore-next-line
		$this->data[$offset] = $value;
	}

	/**
	 * @param key-of<TLocalAndForeign> $offset
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
