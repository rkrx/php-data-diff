<?php

namespace DataDiff\Tools;

class StringTools {
	/**
	 * @param string $input
	 * @param int $maxLength
	 * @return string
	 */
	public static function shorten(string $input, int $maxLength = 32): string {
		$value = (string) preg_replace('{\\s+}', ' ', $input);
		$arr = preg_split('{(?!^)(?=.)}u', $value);
		$arr = $arr !== false ? $arr : [];
		if(count($arr) > $maxLength) {
			$length = $maxLength - 3;
			$partALength = (int) floor($length / 2);
			$partBLength = (int) ceil($length / 2);
			$firstPart = implode('', array_slice($arr, 0, $partALength));
			$lastPart = implode('', array_slice($arr, -$partBLength));
			$value = sprintf('%s...%s', $firstPart, $lastPart);
		}

		return $value;
	}

	/**
	 * @param mixed $data
	 * @return string
	 */
	public static function jsonEncode($data): string {
		return Json::encode($data);
	}
}
