<?php

namespace DataDiff\Tools;

use DataDiff\TestData\AnnotatedModel;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class SchemaFromModelExtractorTest extends TestCase {
	public function testExtractSchemaFromModel(): void {
		[$keySchema, $valueSchema] = ModelTools::getSchemaFromModel(AnnotatedModel::class);
		self::assertEquals(['id' => 'STR'], $keySchema);
		self::assertEquals(['quantity' => 'INT', 'active' => 'BOOL', 'amount' => 'MONEY', 'created_at' => 'STR'], $valueSchema);
	}

	public function testExtractDataFromModel(): void {
		$dt = new DateTimeImmutable('2022-07-26 12:00:00');
		$model = new AnnotatedModel('abc', 5, true, 9.99, $dt);
		$values = ModelTools::getValuesFromModel($model, AnnotatedModel::class);
		self::assertEquals(['id' => 'abc', 'quantity' => 5, 'active' => true, 'amount' => 9.99, 'created_at' => $dt], $values);
	}
}
