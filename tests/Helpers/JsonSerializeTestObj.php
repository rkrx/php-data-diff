<?php
namespace DataDiff\Helpers;

use JsonSerializable;

class JsonSerializeTestObj implements JsonSerializable {
	/** @var array */
	private $data;

	/**
	 * @param array $data
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
