<?php
namespace DataDiff\Tools;

class StringShortener {
	/**
	 * @param string $input
	 * @param int $maxLength
	 * @return string
	 */
	public static function shorten($input, $maxLength = 32) {
		$value = preg_replace('/\\s+/', ' ', $input);
		$arr = preg_split('/(?!^)(?=.)/u', $value);
		if(count($arr) > $maxLength) {
			$length = $maxLength - 3;
			$partALength = floor($length / 2);
			$partBLength = ceil($length / 2);
			$value = join('', array_slice($arr, 0, $partALength)) . '...' . join('', array_slice($arr, -$partBLength));
		}
		return $value;
	}
}
