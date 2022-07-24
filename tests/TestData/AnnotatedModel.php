<?php

namespace DataDiff\TestData;

use DataDiff\Attributes\DataDiffProp;
use DataDiff\MemoryDiffStorage;
use DateTimeInterface;

class AnnotatedModel {
	/** @var string */
	#[DataDiffProp(type: MemoryDiffStorage::STR, fieldName: 'id', key: true)]
	public $id;

	/** @var int */
	#[DataDiffProp(type: MemoryDiffStorage::INT, fieldName: 'quantity')]
	public $qty;

	/** @var bool */
	#[DataDiffProp(type: MemoryDiffStorage::BOOL, fieldName: 'active')]
	public $isActive;

	/** @var float */
	#[DataDiffProp(type: MemoryDiffStorage::MONEY, fieldName: 'amount')]
	public $amountF;

	/** @var DateTimeInterface */
	#[DataDiffProp(type: MemoryDiffStorage::STR, fieldName: 'created_at', options: ['date_format' => 'Y-m-d H:i:s'])]
	public $createdAt;

	public function __construct(string $id, int $qty, bool $isActive, float $amountF, DateTimeInterface $createdAt) {
		$this->id = $id;
		$this->qty = $qty;
		$this->isActive = $isActive;
		$this->amountF = $amountF;
		$this->createdAt = $createdAt;
	}
}
