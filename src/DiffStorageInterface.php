<?php
namespace DataDiff;

interface DiffStorageInterface {
	/**
	 * @return string[]
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
