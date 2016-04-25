<?php
namespace DataDiff;

interface DiffStorageStoreRowDataInterface {
	/**
	 * @param array $options
	 * @return array
	 */
	public function getData(array $options = []);

	/**
	 * @param array $options
	 * @return array
	 */
	public function getKeyData(array $options = []);

	/**
	 * @param array $options
	 * @return array
	 */
	public function getValueData(array $options = []);
}
