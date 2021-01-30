<?php
namespace DataDiff;

use JsonSerializable;
use ArrayAccess;

interface DiffStorageStoreRowInterface extends JsonSerializable, ArrayAccess {
	/**
	 * @return DiffStorageStoreRowDataInterface
	 */
	public function getLocal();

	/**
	 * @return DiffStorageStoreRowDataInterface
	 */
	public function getForeign();

	/**
	 * `$options` are:
	 * * `keys`: Only these keys are considered and returned
	 * * `ignore`: These keys are ignored and omitted
	 *
	 * @param array $options
	 * @return array
	 */
	public function getData(array $options = []): array;

	/**
	 * `$options` are:
	 * * `keys`: Only these keys are considered and returned
	 * * `ignore`: These keys are ignored and omitted
	 *
	 * @param array $options
	 * @return array
	 */
	public function getForeignData(array $options = []): array;

	/**
	 * @param array|null $fields
	 * @return array
	 */
	public function getDiff(array $fields = null): array;

	/**
	 * @param array|null $fields
	 * @param string|null $format
	 * @return string
	 */
	public function getDiffFormatted(?array $fields = null, ?string $format = null): string;

	/**
	 * @return mixed
	 */
	public function jsonSerialize();

	/**
	 * @param mixed $offset
	 * @return bool true on success or false on failure.
	 */
	public function offsetExists($offset): bool;

	/**
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet($offset);

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 * @return void
	 */
	public function offsetSet($offset, $value): void;

	/**
	 * @param mixed $offset
	 * @return void
	 */
	public function offsetUnset($offset): void;

	/**
	 * @return string
	 */
	public function __toString(): string;
}
