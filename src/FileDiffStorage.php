<?php
namespace DataDiff;

use DataDiff\Exceptions\EmptySchemaException;
use DataDiff\Exceptions\InvalidSchemaException;

class FileDiffStorage extends DiffStorage {
	/**
	 * @param string|null $filename
	 * @param array $keySchema
	 * @param array $valueSchema
	 * @param array $options
	 *
	 * @throws EmptySchemaException
	 * @throws InvalidSchemaException
	 */
	public function __construct(?string $filename, array $keySchema, array $valueSchema, array $options = []) {
		if($filename === null) {
			$filename = tempnam(sys_get_temp_dir(), 'data-diff-');
		}
		$this->createFile($filename);
		$options['dsn'] = sprintf('sqlite:%s', $filename);
		parent::__construct($keySchema, $valueSchema, $options);
	}

	/**
	 * @param string $filename
	 */
	private function createFile(string $filename) {
		$fp = null;
		try {
			$fp = fopen($filename, 'w+');
		} finally {
			if(is_resource($fp)) {
				fclose($fp);
			}
		}
	}
}
