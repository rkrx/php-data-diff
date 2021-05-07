<?php
namespace DataDiff\Helpers;

use JsonSerializable;

class JsonSerializeTestObj implements JsonSerializable {
	/** @var array<mixed, mixed> */
	private $data;

	/**
	 * @param array<mixed, mixed> $data
	 */
	public function __construct(array $data) {
		$this->data = $data;
	}

	/**
	 * @return mixed
	 */
	public function jsonSerialize() {
		return $this->data;
	}
}
