<?php

namespace DataDiff\Builders;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class MemoryDiffStorageBuilderTest extends TypeInferenceTestCase {
	#[DataProvider('dataFileAsserts')]
	public function test(string $assertType, string $file, mixed ...$args): void {
		$this->assertFileAsserts($assertType, $file, ...$args);
	}

	/**
	 * @return iterable<mixed>
	 */
	public static function dataFileAsserts(): iterable {
		yield from self::gatherAssertTypes(__DIR__ . '/Scripts/script.php');
	}

	public static function getAdditionalConfigFiles(): array {
		return [__DIR__ . '/../../extension.neon'];
	}
}
