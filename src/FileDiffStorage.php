<?php
namespace DataDiff;

class FileDiffStorage extends DiffStorage {
	/**
	 * @param string $filename
	 * @param array $keySchema
	 * @param array $valueSchema
	 * @param callable|null $duplicateKeyHandler
	 * @param array $options
	 */
	public function __construct($filename = null, array $keySchema, array $valueSchema, $duplicateKeyHandler, array $options) {
		if($filename === null) {
			$filename = tempnam(sys_get_temp_dir(), 'data-diff-');
		}
		$this->createFile($filename);
		$options['dsn'] = sprintf('sqlite:%s', $filename);
		parent::__construct($keySchema, $valueSchema, $duplicateKeyHandler, $options);
	}

	/**
	 * @param string $filename
	 */
	private function createFile($filename) {
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
