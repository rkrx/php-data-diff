<?php
namespace DataDiff;

/**
 * @template TKeySpec of array<string, mixed>
 * @template TValueSpec of array<string, mixed>
 * @template TExtraSpec of array<string, mixed>
 */
interface DiffStorageInterface {
	/**
	 * @return string[]
	 */
	public function getKeys(): array;

	/**
	 * @return DiffStorageStore<TKeySpec, TValueSpec&TExtraSpec, TKeySpec&TValueSpec&TExtraSpec>
	 */
	public function storeA(): DiffStorageStore;

	/**
	 * @return DiffStorageStore<TKeySpec, TValueSpec&TExtraSpec, TKeySpec&TValueSpec&TExtraSpec>
	 */
	public function storeB(): DiffStorageStore;
}
