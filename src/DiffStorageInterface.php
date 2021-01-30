<?php
namespace DataDiff;

interface DiffStorageInterface {
	/**
	 * @return array
	 */
	public function getKeys(): array;

	/**
	 * @return DiffStorageStore
	 */
	public function storeA(): DiffStorageStore;

	/**
	 * @return DiffStorageStore
	 */
	public function storeB(): DiffStorageStore;
}
