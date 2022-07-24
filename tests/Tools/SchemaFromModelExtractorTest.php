<?php

namespace DataDiff\Tools;

use DataDiff\TestData\AnnotatedModel;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class SchemaFromModelExtractorTest extends TestCase {
	public function testExtractSchemaFromModel(): void {
		$model = new AnnotatedModel('abc', 5, true, 9.99, new DateTimeImmutable('2022-07-26 12:00:00'));
		[$keySchema, $valueSchema] = ModelTools::getSchemaFromModel($model);
		self::assertEquals(['id' => 'STR'], $keySchema);
		self::assertEquals(['quantity' => 'INT', 'active' => 'BOOL', 'amount' => 'MONEY', 'created_at' => 'STR'], $valueSchema);
	}

	public function testExtractDataFromModel(): void {
		$model = new AnnotatedModel('abc', 5, true, 9.99, new DateTimeImmutable('2022-07-26 12:00:00'));
		$values = ModelTools::getValuesFromModel($model, AnnotatedModel::class);
		self::assertEquals(['id' => 'abc', 'quantity' => 5, 'active' => true, 'amount' => 9.99, 'created_at' => '2022-07-26 12:00:00'], $values);
	}
}
