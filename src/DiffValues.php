<?php

namespace DataDiff;

class DiffValues {
	/**
	 * Returns the values that are new in $second and missing from $second compared to $first.
	 *
	 * @template T
	 * @param array<array-key, T> $first
	 * @param array<array-key, T> $second
	 * @return array{new: list<T>, missing: list<T>}
	 */
	public static function computeDifferencesInSecond(array $first, array $second): array {
		return [
			'new' => self::getValuesNewInSecondArray($first, $second),
			'missing' => self::getValuesMissingInSecondArray($first, $second),
		];
	}

	/**
	 * Returns the values that are missing in $second but are present in $first.
	 *
	 * @template T
	 * @param array<array-key, T> $first
	 * @param array<array-key, T> $second
	 * @return list<T>
	 */
	public static function getValuesMissingInSecondArray(array $first, array $second): array {
		return array_values(array_diff($first, $second));
	}

	/**
	 * Returns the values that are new in $second compared to $first.
	 *
	 * @template T
	 * @param array<array-key, T> $first
	 * @param array<array-key, T> $second
	 * @return list<T>
	 */
	public static function getValuesNewInSecondArray(array $first, array $second): array {
		return self::getValuesMissingInSecondArray($second, $first);
	}
}
