<?php

namespace DataDiff;

use rkrMerge;

/**
 * @template TKeySpec of array<string, mixed>
 * @template TValueSpec of array<string, mixed>
 * @template TExtraSpec of array<string, mixed>
 *
 * @phpstan-type TFullValueSpec rkrMerge<TValueSpec, TExtraSpec>
 */
interface DiffStorageInterface {
	/**
	 * @return string[]
	 */
	public function getKeys(): array;

	/**
	 * @return DiffStorageStore<TKeySpec, TFullValueSpec, rkrMerge<TKeySpec, TFullValueSpec>>
	 */
	public function storeA(): DiffStorageStore;

	/**
	 * @return DiffStorageStore<TKeySpec, TFullValueSpec, rkrMerge<TKeySpec, TFullValueSpec>>
	 */
	public function storeB(): DiffStorageStore;
}
