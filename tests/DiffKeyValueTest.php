<?php

namespace DataDiff;

use PHPUnit\Framework\TestCase;

class DiffKeyValueTest extends TestCase {
	public function testGetDifferencesInCommonKeysFromSecond(): void {
		$result = DiffKeyValue::getDifferencesInCommonKeysFromSecond(['a' => 1, 'c' => 1], ['a' => 2, 'b' => 2]);
		self::assertEquals(['a' => 2], $result);
	}

	public function testGetMissingKeysFromSecondArray(): void {
		$result = DiffKeyValue::getKeysMissingInSecondArray(['a' => 1, 'c' => 1], ['a' => 1, 'b' => 2]);
		self::assertEquals(['c'], $result);
	}

	public function testComputeDifferencesInSecond(): void {
		$first = ['a' => 1, 'c' => 1, 'd' => 3];
		$second = ['a' => 2, 'b' => 2, 'd' => 3];

		$result = DiffKeyValue::computeDifferencesInSecond($first, $second);

		$expected = [
			'new' => ['b' => 2],
			'missing' => ['c' => 1],
			'changed' => ['a' => 2],
			'unchanged' => ['d' => 3],
		];

		self::assertEquals($expected, $result);
	}

	public function testComputeChangedKeysInSecond(): void {
		$first = ['a' => 1, 'c' => 1, 'd' => 3];
		$second = ['a' => 2, 'b' => 2, 'd' => 3];

		$result = DiffKeyValue::computeChangedKeysInSecond($first, $second);

		$expected = [
			'new' => ['b'],
			'missing' => ['c'],
			'changed' => ['a'],
			'unchanged' => ['d'],
		];

		self::assertEquals($expected, $result);
	}
}
