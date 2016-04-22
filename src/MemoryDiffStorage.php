<?php
namespace DataDiff;

class MemoryDiffStorage extends DiffStorage {
	/**
	 * @param array $keySchema
	 * @param array $valueSchema
	 * @param array $options
	 */
	public function __construct(array $keySchema, array $valueSchema, array $options = []) {
		if(!array_key_exists('dsn', $options)) {
			$options['dsn'] = 'sqlite::memory:';
		}
		parent::__construct($keySchema, $valueSchema, $options);
	}
}
