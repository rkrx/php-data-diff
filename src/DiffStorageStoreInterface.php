<?php

namespace DataDiff;

use Countable;
use Generator;
use IteratorAggregate;
use Traversable;

/**
 * @template TKeySpec of array<string, mixed>
 * @template TValueSpec of array<string, mixed>
 * @template TLocal of array<string, mixed>
 * @template TForeign of array<string, mixed>
 *
 * @extends IteratorAggregate<TLocal>
 */
interface DiffStorageStoreInterface extends Countable, IteratorAggregate {
	/**
	 * @param array<string, mixed> $data
	 * @param null|array<string, string> $translation
	 * @param null|callable(array<string, mixed>, array<string, mixed>): array<string, null|scalar> $duplicateKeyHandler
	 */
	public function addRow(array $data, ?array $translation = null, ?callable $duplicateKeyHandler = null): void;

	/**
	 * @param Generator<array<string, mixed>>|iterable<array<string, mixed>> $rows
	 * @param null|array<string, string> $translation
	 * @param null|callable(array<string, mixed>, array<string, mixed>): array<string, null|scalar> $duplicateKeyHandler
	 * @return $this
	 */
	public function addRows($rows, ?array $translation = null, ?callable $duplicateKeyHandler = null);

	/**
	 * Returns true whenever there is any changed, added or removed data available
	 *
	 * @return bool
	 */
	public function hasAnyChanges(): bool;

	/**
	 * Get all rows, that have a different value hash in the other store
	 *
	 * @param array<string, int|string> $arguments
	 * @return Traversable<DiffStorageStoreRow<TKeySpec, TValueSpec, TLocal, TForeign>>
	 */
	public function getUnchanged(array $arguments = []);

	/**
	 * Get all rows, that are present in this store, but not in the other
	 *
	 * @param array<string, int|string> $arguments
	 * @return Traversable<DiffStorageStoreRow<TKeySpec, TValueSpec, TLocal, TForeign>>
	 */
	public function getNew(array $arguments = []);

	/**
	 * Get all rows, that have a different value hash in the other store
	 *
	 * @param array<string, int|string> $arguments
	 * @return Traversable<DiffStorageStoreRow<TKeySpec, TValueSpec, TLocal, TForeign>>
	 */
	public function getChanged(array $arguments = []);

	/**
	 * Get all rows, that are present in this store, but not in the other and
	 * get all rows, that have a different value hash in the other store
	 *
	 * @param array<string, int|string> $arguments
	 * @return Traversable<DiffStorageStoreRow<TKeySpec, TValueSpec, TLocal, TForeign>>
	 */
	public function getNewOrChanged(array $arguments = []);

	/**
	 * Get all rows, that are present in the other store, but not in this
	 *
	 * @param array<string, int|string> $arguments
	 * @return Traversable<DiffStorageStoreRow<TKeySpec, TValueSpec, TLocal, TForeign>>
	 */
	public function getMissing(array $arguments = []);

	/**
	 * Get all rows, that are present in this store, but not in the other and
	 * get all rows, that have a different value hash in the other store and
	 * get all rows, that are present in the other store, but not in this
	 *
	 * @param array<string, int|string> $arguments
	 * @return Traversable<DiffStorageStoreRow<TKeySpec, TValueSpec, TLocal, TForeign>>
	 */
	public function getNewOrChangedOrMissing(array $arguments = []);

	/**
	 * @return $this
	 */
	public function clearAll();

	/**
	 * @return Traversable<TLocal>
	 */
	public function getIterator(): Traversable;

	/**
	 * @return int
	 */
	public function count(): int;
}
