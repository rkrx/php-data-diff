<?php
namespace DataDiff;

use Exception;

interface DiffStorageStoreRowInterface {
	/**
	 * @return array
	 */
	public function getData();

	/**
	 * @return array
	 */
	public function getForeignData();

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

	/**
	 * @return mixed
	 */
	function jsonSerialize();

	/**
	 * @param mixed $offset
	 * @return boolean true on success or false on failure.
	 */
	public function offsetExists($offset);

	/**
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet($offset);

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 * @return void
	 */
	public function offsetSet($offset, $value);

	/**
	 * @param mixed $offset
	 * @return void
	 */
	public function offsetUnset($offset);
}
