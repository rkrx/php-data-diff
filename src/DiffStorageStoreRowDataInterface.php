<?php

namespace DataDiff;

use Exception;

/**
 * @template TLocal of array<string, mixed>
 * @template TForeign of array<string, mixed>
 *
 * @phpstan-type TConverter callable(mixed): (scalar|null)
 * @phpstan-type TStringFormatterFn callable(DiffStorageStoreRow<TLocal, TForeign>): string
 */
interface DiffStorageStoreRowDataInterface {
	/**
	 * @param array<string, mixed> $options
	 * @return TLocal
	 */
	public function getData(array $options = []): array;

	/**
	 * @param array<string, mixed> $options
	 * @return TForeign
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
	 * @param null|list<key-of<TLocal>> $fields
	 * @return array<string, array{local: TLocal, foreign: TForeign}>
	 */
	public function getDiff(?array $fields = null): array;

	/**
	 * @param null|list<key-of<TLocal>> $fields
	 * @param null|string $format
	 * @return string
	 * @throws Exception
	 */
	public function getDiffFormatted(?array $fields = null, ?string $format = null): string;
}
