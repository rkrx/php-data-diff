<?php

namespace DataDiff;

use PHPUnit\Framework\TestCase;

class DiffValuesTest extends TestCase {
	public function testGetValuesMissingInSecondArray(): void {
		$result = DiffValues::getValuesMissingInSecondArray(['a', 'c', 'd'], ['a', 'b', 'd']);
		self::assertEquals(['c'], $result);
	}

	public function testGetValuesNewInSecondArray(): void {
		$result = DiffValues::getValuesNewInSecondArray(['a', 'c', 'd'], ['a', 'b', 'd']);
		self::assertEquals(['b'], $result);
	}

	public function testComputeDifferencesInSecond(): void {
		$first = ['a', 'c', 'd'];
		$second = ['a', 'b', 'd'];

		$result = DiffValues::computeDifferencesInSecond($first, $second);

		$expected = [
			'new' => ['b'],
			'missing' => ['c'],
		];

		self::assertEquals($expected, $result);
	}
}
