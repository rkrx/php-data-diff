<?php

namespace DataDiff;

use ArrayAccess;
use JsonSerializable;
use ReturnTypeWillChange;

/**
 * @template TKeySpec of array<string, mixed>
 * @template TValueSpec of array<string, mixed>
 * @template TLocal of array<string, mixed>
 * @template TForeign of array<string, mixed>
 *
 * @phpstan-type TLocalOrForeign TLocal|TForeign
 *
 * @extends ArrayAccess<key-of<TLocalOrForeign>, value-of<TLocalOrForeign>>
 */
interface DiffStorageStoreRowInterface extends JsonSerializable, ArrayAccess {
	/**
	 * @return DiffStorageStoreRowDataInterface<TKeySpec, TValueSpec, TLocal, TForeign>
	 */
	public function getLocal();

	/**
	 * @return DiffStorageStoreRowDataInterface<TKeySpec, TValueSpec, TLocal, TForeign>
	 */
	public function getForeign();

	/**
	 * `$options` are:
	 * * `keys`: Only these keys are considered and returned
	 * * `ignore`: These keys are ignored and omitted
	 *
	 * @param array<string, mixed> $options
	 * @return TLocal
	 */
	public function getData(array $options = []): array;

	/**
	 * `$options` are:
	 * * `keys`: Only these keys are considered and returned
	 * * `ignore`: These keys are ignored and omitted
	 *
	 * @param array<string, mixed> $options
	 * @return TForeign
	 */
	public function getForeignData(array $options = []): array;

	/**
	 * @param null|string[] $fields
	 * @return array<string, array{local: TLocal, foreign: TForeign}>
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
	#[ReturnTypeWillChange]
	public function jsonSerialize();

	/**
	 * @param key-of<TLocalOrForeign> $offset
	 * @return bool true on success or false on failure.
	 */
	public function offsetExists($offset): bool;

	/**
	 * @param key-of<TLocalOrForeign> $offset
	 * @return value-of<TLocalOrForeign>
	 */
	#[ReturnTypeWillChange]
	public function offsetGet($offset);

	/**
	 * @param key-of<TLocalOrForeign> $offset
	 * @param value-of<TLocalOrForeign> $value
	 * @return void
	 */
	public function offsetSet($offset, $value): void;

	/**
	 * @param key-of<TLocalOrForeign> $offset
	 * @return void
	 */
	public function offsetUnset($offset): void;

	/**
	 * @return string
	 */
	public function __toString(): string;
}
