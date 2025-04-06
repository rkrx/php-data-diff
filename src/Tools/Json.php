<?php

namespace DataDiff\Tools;

class Json {
	/**
	 * @param mixed $input
	 * @return string
	 */
	public static function encode($input): string {
		$str = json_encode($input, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		$lastError = json_last_error();
		if($lastError === JSON_ERROR_NONE) {
			return (string) $str;
		}
		if($lastError === JSON_ERROR_UTF8) {
			$input = self::fixEncoding($input);

			return self::encode($input);
		}
		if($str === false) {
			$code = json_last_error();
			$msg = json_last_error_msg();
			throw new JsonException($msg, $code);
		}

		return $str;
	}

	/**
	 * @param mixed $data Input could be anything from scalar values to objects and arrays
	 * @return mixed Pretty much the same as the input, except that objects are converted to arrays
	 */
	private static function fixEncoding($data) {
		if(is_object($data)) {
			$data = (array) $data;
		}
		if(is_array($data)) {
			$fn = static function ($value) {
				return self::fixEncoding($value);
			};
			$data = array_map($fn, $data);
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
