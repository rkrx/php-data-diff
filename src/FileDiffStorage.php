<?php
namespace DataDiff;

use DataDiff\Exceptions\EmptySchemaException;
use DataDiff\Exceptions\InvalidSchemaException;

class FileDiffStorage extends DiffStorage {
	/**
	 * @param string|null $filename
	 * @param array<string, string> $keySchema
	 * @param array<string, string> $valueSchema
	 * @param array<string, mixed> $options
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
