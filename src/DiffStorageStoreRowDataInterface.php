<?php
namespace DataDiff;

use Exception;

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
	public function getForeignData(array $options = []);

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

	/**
	 * @param array $fields
	 * @return array
	 */
	public function getDiff(array $fields = null);

	/**
	 * @param array $fields
	 * @param mixed $format
	 * @return array
	 * @throws Exception
	 */
	public function getDiffFormatted(array $fields = null, $format = null);
}
