<?php
namespace DataDiff;

use DataDiff\Exceptions\EmptySchemaException;
use DataDiff\Exceptions\InvalidSchemaException;
use DataDiff\Tools\ModelTools;

class MemoryDiffStorage extends DiffStorage {
	/**
	 * @param object $model
	 * @param array<string, mixed> $options
	 * @return self
	 *
	 * @throws EmptySchemaException
	 * @throws InvalidSchemaException
	 */
	public static function fromModelWithAttributes($model, array $options = []): self {
		[$keySchema, $valueSchema] = ModelTools::getSchemaFromModel($model);
		return new self($keySchema, $valueSchema, $options);
	}

	/**
	 * @param array<string, string> $keySchema
	 * @param array<string, string> $valueSchema
	 * @param array<string, mixed> $options
	 *
	 * @throws EmptySchemaException
	 * @throws InvalidSchemaException
	 */
	public function __construct(array $keySchema, array $valueSchema, array $options = []) {
		if(!array_key_exists('dsn', $options)) {
			$options['dsn'] = 'sqlite::memory:';
		}
		parent::__construct($keySchema, $valueSchema, $options);
	}
}
