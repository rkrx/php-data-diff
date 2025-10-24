<?php

namespace DataDiff;

use PHPUnit\Framework\TestCase;

class DiffKeyValueTest extends TestCase {
	public function testGetMissingKeysFromSecondArray(): void {
		$result = DiffKeyValue::getKeysMissingInSecondArray(['a' => 1, 'c' => 1], ['a' => 1, 'b' => 2]);
		self::assertEquals(['c'], $result);
	}

	public function testGetDifferencesInCommonKeysFromSecond(): void {
		$result = DiffKeyValue::getDifferencesInCommonKeysFromSecond(['a' => 1, 'c' => 1], ['a' => 2, 'b' => 2]);
		self::assertEquals(['a' => 2], $result);
	}
}
