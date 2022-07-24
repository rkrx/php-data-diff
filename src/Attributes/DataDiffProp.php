<?php

namespace DataDiff\Attributes;

use Attribute;

#[Attribute]
class DataDiffProp {
	/** @var string */
	public $type;
	/** @var string|null */
	public $fieldName;
	/** @var bool */
	public $isKey;
	/** @var array{date_format?: string} */
	public $options;

	/**
	 * @param string $type
	 * @param string|null $fieldName
	 * @param bool $key
	 * @param array{date_format?: string} $options
	 */
	public function __construct(string $type, ?string $fieldName, bool $key = false, array $options = []) {
		$this->type = $type;
		$this->fieldName = $fieldName;
		$this->isKey = $key;
		$this->options = $options;
	}
}
