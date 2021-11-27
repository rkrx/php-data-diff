<?php
namespace DataDiff;

use JsonSerializable;
use ArrayAccess;

/**
 * @extends ArrayAccess<string, mixed>
 */
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
	 * @param array<string, mixed> $options
	 * @return array<string, mixed>
	 */
	public function getData(array $options = []): array;

	/**
	 * `$options` are:
	 * * `keys`: Only these keys are considered and returned
	 * * `ignore`: These keys are ignored and omitted
	 *
	 * @param array<string, mixed> $options
	 * @return array<string, mixed>
	 */
	public function getForeignData(array $options = []): array;

	/**
	 * @param null|string[] $fields
	 * @return array<string, array{local: mixed, foreign: mixed}>
	 */
	public function getDiff(?array $fields = null): array;

	/**
	 * @param null|array<string, mixed> $fields
	 * @param null|string $format
	 * @return string
	 */
	public function getDiffFormatted(?array $fields = null, ?string $format = null): string;

	/**
	 * @return array<string, mixed>
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize();

	/**
	 * @param string $offset
	 * @return bool true on success or false on failure.
	 */
	public function offsetExists($offset): bool;

	/**
	 * @param string $offset
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet($offset);

	/**
	 * @param string $offset
	 * @param mixed $value
	 * @return void
	 */
	public function offsetSet($offset, $value): void;

	/**
	 * @param string $offset
	 * @return void
	 */
	public function offsetUnset($offset): void;

	/**
	 * @return string
	 */
	public function __toString(): string;
}
