<?php
namespace DataDiff;

use Generator;
use Traversable;

interface DiffStorageStoreInterface {
	/**
	 * @param array $data
	 * @param array $translation
	 * @param callable $duplicateKeyHandler
	 */
	public function addRow(array $data, array $translation = null, $duplicateKeyHandler = null);

	/**
	 * @param Traversable|array $rows
	 * @param array $translation
	 * @param callable $duplicateKeyHandler
	 * @return $this
	 */
	public function addRows($rows, array $translation = null, $duplicateKeyHandler = null);

	/**
	 * Returns true whenever there is any changed, added or removed data available
	 */
	public function hasAnyChanges();

	/**
	 * Get all rows, that are present in this store, but not in the other
	 *
	 * @return Generator|DiffStorageStoreRow[]
	 */
	public function getNew();

	/**
	 * Get all rows, that have a different value hash in the other store
	 *
	 * @return Generator|DiffStorageStoreRow[]
	 */
	public function getChanged();

	/**
	 * @return Generator|DiffStorageStoreRow[]
	 */
	public function getNewOrChanged();

	/**
	 * Get all rows, that are present in the other store, but not in this
	 *
	 * @return Generator|DiffStorageStoreRow[]
	 */
	public function getMissing();

	/**
	 * @return $this
	 */
	public function clearAll();

	/**
	 * @return Traversable|array[]
	 */
	public function getIterator();
}
