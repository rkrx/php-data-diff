<?php

namespace DataDiff\Builders;

use DataDiff\DiffStorageFieldTypeConstants;
use DataDiff\MemoryDiffStorage;

/**
 * @template TKeySpec of array<string, mixed>
 * @phpstan-type TKeyOfKeySpec key-of<TKeySpec>
 *
 * @template TValueSpec of array<string, mixed>
 * @phpstan-type TKeyOfValueSpec key-of<TValueSpec>
 *
 * @template TExtraSpec of array<string, mixed>
 */
class MemoryDiffStorageBuilder {
	/** @var array<string, string> */
	private array $keyFields = [];

	/** @var array<string, string> */
	private array $valueFields = [];

	/**
	 * @template TRowKeyName of array-key&string
	 * @param TRowKeyName $rowKeyName
	 * @return self<TKeySpec, TValueSpec, TExtraSpec>
	 */
	public function addBoolKey(string $rowKeyName): self {
		$this->keyFields[$rowKeyName] = DiffStorageFieldTypeConstants::BOOL;
		return $this;
	}

	/**
	 * @template TRowKeyName of array-key&string
	 * @param TRowKeyName $rowKeyName
	 * @return self<TKeySpec, TValueSpec, TExtraSpec>
	 */
	public function addIntKey(string $rowKeyName): self {
		$this->keyFields[$rowKeyName] = DiffStorageFieldTypeConstants::INT;
		return $this;
	}

	/**
	 * @template TRowKeyName of array-key&string
	 * @param TRowKeyName $rowKeyName
	 * @return self<TKeySpec, TValueSpec, TExtraSpec>
	 */
	public function addFloatKey(string $rowKeyName): self {
		$this->keyFields[$rowKeyName] = DiffStorageFieldTypeConstants::FLOAT;
		return $this;
	}

	/**
	 * @template TRowKeyName of array-key&string
	 * @param TRowKeyName $rowKeyName
	 * @return self<TKeySpec, TValueSpec, TExtraSpec>
	 */
	public function addStringKey(string $rowKeyName): self {
		$this->keyFields[$rowKeyName] = DiffStorageFieldTypeConstants::STR;
		return $this;
	}

	/**
	 * @template TRowKeyName of array-key&string
	 * @param TRowKeyName $rowKeyName
	 * @return self<TKeySpec, TValueSpec, TExtraSpec>
	 */
	public function addMoneyKey(string $rowKeyName): self {
		$this->keyFields[$rowKeyName] = DiffStorageFieldTypeConstants::MONEY;
		return $this;
	}

	/**
	 * @template TRowKeyName of array-key&string
	 * @param TRowKeyName $rowKeyName
	 * @return self<TKeySpec, TValueSpec, TExtraSpec>
	 */
	public function addMd5Key(string $rowKeyName): self {
		$this->keyFields[$rowKeyName] = DiffStorageFieldTypeConstants::MD5;
		return $this;
	}

	/**
	 * @template TRowKeyName of array-key&string
	 * @param TRowKeyName $rowKeyName
	 * @return self<TKeySpec, TValueSpec, TExtraSpec>
	 */
	public function addBoolValue(string $rowKeyName): self {
		$this->valueFields[$rowKeyName] = DiffStorageFieldTypeConstants::BOOL;
		return $this;
	}

	/**
	 * @template TRowKeyName of array-key&string
	 * @param TRowKeyName $rowKeyName
	 * @return self<TKeySpec, TValueSpec, TExtraSpec>
	 */
	public function addIntValue(string $rowKeyName): self {
		$this->valueFields[$rowKeyName] = DiffStorageFieldTypeConstants::INT;
		return $this;
	}

	/**
	 * @template TRowKeyName of array-key&string
	 * @param TRowKeyName $rowKeyName
	 * @return self<TKeySpec, TValueSpec, TExtraSpec>
	 */
	public function addFloatValue(string $rowKeyName): self {
		$this->valueFields[$rowKeyName] = DiffStorageFieldTypeConstants::FLOAT;
		return $this;
	}

	/**
	 * @template TRowKeyName of array-key&string
	 * @param TRowKeyName $rowKeyName
	 * @return self<TKeySpec, TValueSpec, TExtraSpec>
	 */
	public function addStringValue(string $rowKeyName): self {
		$this->valueFields[$rowKeyName] = DiffStorageFieldTypeConstants::STR;
		return $this;
	}

	/**
	 * @template TRowKeyName of array-key&string
	 * @param TRowKeyName $rowKeyName
	 * @return self<TKeySpec, TValueSpec, TExtraSpec>
	 */
	public function addMoneyValue(string $rowKeyName): self {
		$this->valueFields[$rowKeyName] = DiffStorageFieldTypeConstants::MONEY;
		return $this;
	}

	/**
	 * @template TRowKeyName of array-key&string
	 * @param TRowKeyName $rowKeyName
	 * @return self<TKeySpec, TValueSpec, TExtraSpec>
	 */
	public function addMd5Value(string $rowKeyName): self {
		$this->valueFields[$rowKeyName] = DiffStorageFieldTypeConstants::MD5;
		return $this;
	}

	/**
	 * @template TRowKeyName of array-key&string
	 * @param TRowKeyName $rowKeyName
	 * @return self<TKeySpec, TValueSpec, TExtraSpec>
	 */
	public function addBoolExtra(string $rowKeyName): self {
		return $this;
	}

	/**
	 * @template TRowKeyName of array-key&string
	 * @param TRowKeyName $rowKeyName
	 * @return self<TKeySpec, TValueSpec, TExtraSpec>
	 */
	public function addIntExtra(string $rowKeyName): self {
		return $this;
	}

	/**
	 * @template TRowKeyName of array-key&string
	 * @param TRowKeyName $rowKeyName
	 * @return self<TKeySpec, TValueSpec, TExtraSpec>
	 */
	public function addFloatExtra(string $rowKeyName): self {
		return $this;
	}

	/**
	 * @template TRowKeyName of array-key&string
	 * @param TRowKeyName $rowKeyName
	 * @return self<TKeySpec, TValueSpec, TExtraSpec>
	 */
	public function addStringExtra(string $rowKeyName): self {
		return $this;
	}

	/**
	 * @template TRowKeyName of array-key&string
	 * @param TRowKeyName $rowKeyName
	 * @return self<TKeySpec, TValueSpec, TExtraSpec>
	 */
	public function addMoneyExtra(string $rowKeyName): self {
		return $this;
	}

	/**
	 * @template TRowKeyName of array-key&string
	 * @param TRowKeyName $rowKeyName
	 * @return self<TKeySpec, TValueSpec, TExtraSpec>
	 */
	public function addMd5Extra(string $rowKeyName): self {
		return $this;
	}

	/**
	 * @param array{dsn?: string} $options
	 * @return MemoryDiffStorage<TKeySpec, TValueSpec, TExtraSpec>
	 */
	public function build(array $options = []): MemoryDiffStorage {
		/** @var MemoryDiffStorage<TKeySpec, TValueSpec, TExtraSpec> $ds */
		$ds = new MemoryDiffStorage($this->keyFields, $this->valueFields, $options);
		return $ds;
	}
}
