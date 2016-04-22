<?php
namespace DataDiff;

interface DiffStorageInterface {
	/**
	 * @return array
	 */
	public function getKeys();

	/**
	 * @return DiffStorageStore
	 */
	public function storeA();

	/**
	 * @return DiffStorageStore
	 */
	public function storeB();
}
