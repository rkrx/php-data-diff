<?php

namespace DataDiff;

use DataDiff\Exceptions\EmptySchemaException;
use DataDiff\Exceptions\InvalidSchemaException;
use DataDiff\Tools\ModelTools;

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
class MemoryDiffStorage extends DiffStorage {
	/**
	 * @param class-string $fqClassName
	 * @param array{dsn?: string} $options
	 * @return self<array<string, mixed>>
	 *
	 * @throws EmptySchemaException
	 * @throws InvalidSchemaException
	 */
	// @phpstan-ignore-next-line
	public static function fromModelWithAttributes(string $fqClassName, array $options = []): self {
		[$keySchema, $valueSchema] = ModelTools::getSchemaFromModel($fqClassName);

		// @phpstan-ignore-next-line
		return new self($keySchema, $valueSchema, $options);
	}

	/**
	 * @param array<TKeyOfKeySpec, string> $keySchema
	 * @param array<TKeyOfValueSpec, string> $valueSchema
	 * @param array{dsn?: string} $options
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
