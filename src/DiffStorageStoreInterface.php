<?php
namespace DataDiff;

use Traversable;
use JsonSerializable;
use Countable;
use IteratorAggregate;

/**
 * @extends IteratorAggregate<int, array<string, mixed>>
 */
interface DiffStorageStoreInterface extends Countable, IteratorAggregate {
	/**
	 * @param array<string, null|scalar> $data
	 * @param null|array<string, string> $translation
	 * @param null|callable(array<string, null|scalar>, array<string, null|scalar>): array<string, null|scalar> $duplicateKeyHandler
	 */
	public function addRow(array $data, ?array $translation = null, ?callable $duplicateKeyHandler = null): void;

	/**
	 * @param iterable<int, array<string, mixed>|object|JsonSerializable> $rows
	 * @param null|array<string, string> $translation
	 * @param null|callable(array<string, null|scalar>, array<string, null|scalar>): array<string, null|scalar> $duplicateKeyHandler
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
	 * @return Traversable<int, DiffStorageStoreRow>
	 */
	public function getUnchanged(array $arguments = []);

	/**
	 * Get all rows, that are present in this store, but not in the other
	 *
	 * @param array<string, int|string> $arguments
	 * @return Traversable<int, DiffStorageStoreRow>
	 */
	public function getNew(array $arguments = []);

	/**
	 * Get all rows, that have a different value hash in the other store
	 *
	 * @param array<string, int|string> $arguments
	 * @return Traversable<int, DiffStorageStoreRow>
	 */
	public function getChanged(array $arguments = []);

	/**
	 * Get all rows, that are present in this store, but not in the other and
	 * get all rows, that have a different value hash in the other store
	 *
	 * @param array<string, int|string> $arguments
	 * @return Traversable<int, DiffStorageStoreRow>
	 */
	public function getNewOrChanged(array $arguments = []);

	/**
	 * Get all rows, that are present in the other store, but not in this
	 *
	 * @param array<string, int|string> $arguments
	 * @return Traversable<int, DiffStorageStoreRow>
	 */
	public function getMissing(array $arguments = []);

	/**
	 * Get all rows, that are present in this store, but not in the other and
	 * get all rows, that have a different value hash in the other store and
	 * get all rows, that are present in the other store, but not in this
	 *
	 * @param array<string, int|string> $arguments
	 * @return Traversable<int, DiffStorageStoreRow>
	 */
	public function getNewOrChangedOrMissing(array $arguments = []);

	/**
	 * @return $this
	 */
	public function clearAll();

	/**
	 * @return Traversable<int, array<string, mixed>>
	 */
	public function getIterator(): Traversable;

	/**
	 * @return int
	 */
	public function count(): int;
}
