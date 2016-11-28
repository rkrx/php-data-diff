<?php
namespace DataDiff\Tools;

use Exception;

class StringTools {
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
	
	/**
	 * @param mixed $data
	 * @return string
	 * @throws Exception
	 */
	public static function jsonEncode($data) {
		$value = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		$lastError = json_last_error();
		if($lastError === JSON_ERROR_NONE) {
			return $value;
		} elseif($lastError === JSON_ERROR_UTF8) {
			$data = self::fixEncoding($data);
			return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		}
		throw new Exception(json_last_error_msg());
	}
	
	/**
	 * @param mixed $data
	 * @return array
	 */
	private static function fixEncoding($data) {
		if(is_object($data)) {
			$data = (array) $data;
		}
		if(is_array($data)) {
			foreach($data as &$value) {
				$value = self::fixEncoding($value);
			}
		} elseif(is_string($data)) {
			$result = '';
			$enc = 'UTF-8';
			$length = mb_strlen($data, $enc);
			for($i = 0; $i < $length; $i++) {
				$char = mb_substr($data, $i, 1, $enc);
				if(!mb_check_encoding($char, $enc)) {
					$char = chr(0xEF) . chr(0xBF) . chr(0xBD);
				}
				$result .= $char;
			}
			$data = $result;
		}
		return $data;
	}
}
