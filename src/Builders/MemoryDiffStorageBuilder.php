<?php

namespace DataDiff\Builders;

use DataDiff\DiffStorageFieldTypeConstants;
use DataDiff\MemoryDiffStorage;
use rkrAddKey;

/**
 * @template TKeySpec of array<string, mixed>
 * @phpstan-type TKeyOfKeySpec key-of<TKeySpec>
 *
 * @template TValueSpec of array<string, mixed>
 * @phpstan-type TKeyOfValueSpec key-of<TValueSpec>
 *
 * @template TExtraSpec of array<string, mixed>
 *
 * @phpstan-type TBoolField bool|0|1|null
 * @phpstan-type TIntField int|null
 * @phpstan-type TFloatField float|null
 * @phpstan-type TMoneyField float|null
 * @phpstan-type TStringField string|null
 * @phpstan-type TMd5Field string|null
 */
class MemoryDiffStorageBuilder {
	/** @var array<string, string> */
	private array $keyFields = [];

	/** @var array<string, string> */
	private array $valueFields = [];

	/**
	 * @template TRowKeyName of literal-string
	 * @param TRowKeyName $rowKeyName
	 * @return self<rkrAddKey<TKeySpec, TRowKeyName, TBoolField>, TValueSpec, TExtraSpec>
	 */
	public function addBoolKey(string $rowKeyName): self {
		$this->keyFields[$rowKeyName] = DiffStorageFieldTypeConstants::BOOL;

		return $this;
	}

	/**
	 * @template TRowKeyName of literal-string
	 * @param TRowKeyName $rowKeyName
	 * @return self<rkrAddKey<TKeySpec, TRowKeyName, TIntField>, TValueSpec, TExtraSpec>
	 */
	public function addIntKey(string $rowKeyName): self {
		$this->keyFields[$rowKeyName] = DiffStorageFieldTypeConstants::INT;

		return $this;
	}

	/**
	 * @template TRowKeyName of literal-string
	 * @param TRowKeyName $rowKeyName
	 * @return self<rkrAddKey<TKeySpec, TRowKeyName, TFloatField>, TValueSpec, TExtraSpec>
	 */
	public function addFloatKey(string $rowKeyName): self {
		$this->keyFields[$rowKeyName] = DiffStorageFieldTypeConstants::FLOAT;

		return $this;
	}

	/**
	 * @template TRowKeyName of literal-string
	 * @param TRowKeyName $rowKeyName
	 * @return self<rkrAddKey<TKeySpec, TRowKeyName, TStringField>, TValueSpec, TExtraSpec>
	 */
	public function addStringKey(string $rowKeyName): self {
		$this->keyFields[$rowKeyName] = DiffStorageFieldTypeConstants::STR;

		return $this;
	}

	/**
	 * @template TRowKeyName of literal-string
	 * @param TRowKeyName $rowKeyName
	 * @return self<rkrAddKey<TKeySpec, TRowKeyName, TMoneyField>, TValueSpec, TExtraSpec>
	 */
	public function addMoneyKey(string $rowKeyName): self {
		$this->keyFields[$rowKeyName] = DiffStorageFieldTypeConstants::MONEY;

		return $this;
	}

	/**
	 * @template TRowKeyName of literal-string
	 * @param TRowKeyName $rowKeyName
	 * @return self<rkrAddKey<TKeySpec, TRowKeyName, TMd5Field>, TValueSpec, TExtraSpec>
	 */
	public function addMd5Key(string $rowKeyName): self {
		$this->keyFields[$rowKeyName] = DiffStorageFieldTypeConstants::MD5;

		return $this;
	}

	/**
	 * @template TRowKeyName of literal-string
	 * @param TRowKeyName $rowKeyName
	 * @return self<TKeySpec, rkrAddKey<TValueSpec, TRowKeyName, TBoolField>, TExtraSpec>
	 */
	public function addBoolValue(string $rowKeyName): self {
		$this->valueFields[$rowKeyName] = DiffStorageFieldTypeConstants::BOOL;

		return $this;
	}

	/**
	 * @template TRowKeyName of literal-string
	 * @param TRowKeyName $rowKeyName
	 * @return self<TKeySpec, rkrAddKey<TValueSpec, TRowKeyName, TIntField>, TExtraSpec>
	 */
	public function addIntValue(string $rowKeyName): self {
		$this->valueFields[$rowKeyName] = DiffStorageFieldTypeConstants::INT;

		return $this;
	}

	/**
	 * @template TRowKeyName of literal-string
	 * @param TRowKeyName $rowKeyName
	 * @return self<TKeySpec, rkrAddKey<TValueSpec, TRowKeyName, TFloatField>, TExtraSpec>
	 */
	public function addFloatValue(string $rowKeyName): self {
		$this->valueFields[$rowKeyName] = DiffStorageFieldTypeConstants::FLOAT;

		return $this;
	}

	/**
	 * @template TRowKeyName of literal-string
	 * @param TRowKeyName $rowKeyName
	 * @return self<TKeySpec, rkrAddKey<TValueSpec, TRowKeyName, TStringField>, TExtraSpec>
	 */
	public function addStringValue(string $rowKeyName): self {
		$this->valueFields[$rowKeyName] = DiffStorageFieldTypeConstants::STR;

		return $this;
	}

	/**
	 * @template TRowKeyName of literal-string
	 * @param TRowKeyName $rowKeyName
	 * @return self<TKeySpec, rkrAddKey<TValueSpec, TRowKeyName, TMoneyField>, TExtraSpec>
	 */
	public function addMoneyValue(string $rowKeyName): self {
		$this->valueFields[$rowKeyName] = DiffStorageFieldTypeConstants::MONEY;

		return $this;
	}

	/**
	 * @template TRowKeyName of literal-string
	 * @param TRowKeyName $rowKeyName
	 * @return self<TKeySpec, rkrAddKey<TValueSpec, TRowKeyName, TMd5Field>, TExtraSpec>
	 */
	public function addMd5Value(string $rowKeyName): self {
		$this->valueFields[$rowKeyName] = DiffStorageFieldTypeConstants::MD5;

		return $this;
	}

	/**
	 * @template TRowKeyName of literal-string
	 * @param TRowKeyName $rowKeyName
	 * @return self<TKeySpec, TValueSpec, rkrAddKey<TExtraSpec, TRowKeyName, TBoolField>>
	 */
	public function addBoolExtra(string $rowKeyName): self {
		return $this;
	}

	/**
	 * @template TRowKeyName of literal-string
	 * @param TRowKeyName $rowKeyName
	 * @return self<TKeySpec, TValueSpec, rkrAddKey<TExtraSpec, TRowKeyName, TIntField>>
	 */
	public function addIntExtra(string $rowKeyName): self {
		return $this;
	}

	/**
	 * @template TRowKeyName of literal-string
	 * @param TRowKeyName $rowKeyName
	 * @return self<TKeySpec, TValueSpec, rkrAddKey<TExtraSpec, TRowKeyName, TFloatField>>
	 */
	public function addFloatExtra(string $rowKeyName): self {
		return $this;
	}

	/**
	 * @template TRowKeyName of literal-string
	 * @param TRowKeyName $rowKeyName
	 * @return self<TKeySpec, TValueSpec, rkrAddKey<TExtraSpec, TRowKeyName, TStringField>>
	 */
	public function addStringExtra(string $rowKeyName): self {
		return $this;
	}

	/**
	 * @template TRowKeyName of literal-string
	 * @param TRowKeyName $rowKeyName
	 * @return self<TKeySpec, TValueSpec, rkrAddKey<TExtraSpec, TRowKeyName, TMoneyField>>
	 */
	public function addMoneyExtra(string $rowKeyName): self {
		return $this;
	}

	/**
	 * @template TRowKeyName of literal-string
	 * @param TRowKeyName $rowKeyName
	 * @return self<TKeySpec, TValueSpec, rkrAddKey<TExtraSpec, TRowKeyName, TMd5Field>>
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
