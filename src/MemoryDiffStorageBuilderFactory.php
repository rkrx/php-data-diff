<?php

namespace DataDiff;

use DataDiff\Builders\MemoryDiffStorageBuilder;

class MemoryDiffStorageBuilderFactory {
	/**
	 * @return MemoryDiffStorageBuilder<array{}, array{}, array{}>
	 */
	public function createBuilder(): MemoryDiffStorageBuilder {
		/** @var MemoryDiffStorageBuilder<array{}, array{}, array{}> $builder */
		$builder = new MemoryDiffStorageBuilder();

		return $builder;
	}
}
