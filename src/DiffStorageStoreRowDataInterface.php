<?php
namespace DataDiff;

use Exception;

interface DiffStorageStoreRowDataInterface {
	/**
	 * @param array $options
	 * @return array
	 */
	public function getData(array $options = []): array;

	/**
	 * @param array $options
	 * @return array
	 */
	public function getForeignData(array $options = []): array;

	/**
	 * @param array $options
	 * @return array
	 */
	public function getKeyData(array $options = []): array;

	/**
	 * @param array $options
	 * @return array
	 */
	public function getValueData(array $options = []): array;

	/**
	 * @param array|null $fields
	 * @return array
	 */
	public function getDiff(?array $fields = null): array;

	/**
	 * @param array|null $fields
	 * @param string|null $format
	 * @return string
	 * @throws Exception
	 */
	public function getDiffFormatted(?array $fields = null, ?string $format = null): string;
}
