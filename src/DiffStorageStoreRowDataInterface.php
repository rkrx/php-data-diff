<?php
namespace DataDiff;

use Exception;

interface DiffStorageStoreRowDataInterface {
	/**
	 * @param array<string, mixed> $options
	 * @return array<string, mixed>
	 */
	public function getData(array $options = []): array;

	/**
	 * @param array<string, mixed> $options
	 * @return array<string, mixed>
	 */
	public function getForeignData(array $options = []): array;

	/**
	 * @param array<string, mixed> $options
	 * @return array<string, mixed>
	 */
	public function getKeyData(array $options = []): array;

	/**
	 * @param array<string, mixed> $options
	 * @return array<string, mixed>
	 */
	public function getValueData(array $options = []): array;

	/**
	 * @param null|string[] $fields
	 * @return array<string, array{local: mixed, foreign: mixed}>
	 */
	public function getDiff(?array $fields = null): array;

	/**
	 * @param null|string[] $fields
	 * @param null|string $format
	 * @return string
	 * @throws Exception
	 */
	public function getDiffFormatted(?array $fields = null, ?string $format = null): string;
}
