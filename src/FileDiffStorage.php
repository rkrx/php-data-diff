<?php

namespace DataDiff;

use DataDiff\Exceptions\EmptySchemaException;
use DataDiff\Exceptions\InvalidSchemaException;

/**
 * @template TKeySpec of array<string, mixed>
 * @phpstan-type TKeyOfKeySpec key-of<TKeySpec>
 *
 * @template TValueSpec of array<string, mixed>
 * @phpstan-type TKeyOfValueSpec key-of<TValueSpec>
 *
 * @template TExtraSpec of array<string, mixed>
 *
 * @extends DiffStorage<TKeySpec, TValueSpec, TExtraSpec>
 */
class FileDiffStorage extends DiffStorage {
	/**
	 * @param string|null $filename
	 * @param array<TKeyOfKeySpec, string> $keySchema
	 * @param array<TKeyOfValueSpec, string> $valueSchema
	 * @param array{dsn?: string} $options
	 *
	 * @throws EmptySchemaException
	 * @throws InvalidSchemaException
	 */
	public function __construct(?string $filename, array $keySchema, array $valueSchema, array $options = []) {
		if($filename === null) {
			$filename = tempnam(sys_get_temp_dir(), 'data-diff-');
		}
		$this->createFile((string) $filename);
		$options['dsn'] = sprintf('sqlite:%s', $filename);
		parent::__construct($keySchema, $valueSchema, $options);
	}

	/**
	 * @param string $filename
	 */
	private function createFile(string $filename): void {
		$fp = null;
		try {
			$fp = fopen($filename, 'wb+');
		} finally {
			if(is_resource($fp)) {
				fclose($fp);
			}
		}
	}
}
