<?php

namespace DataDiff;

class DiffKeyValue {
	/**
	 * Returns the keys that are missing in $second but are present in $first.
	 *
	 * @template KA of array-key
	 * @template KB of array-key
	 * @template VA
	 * @template VB
	 * @param array<KA, VA> $first
	 * @param array<KB, VB> $second
	 * @return array{new: array<KB, VB>, missing: array<KA, VA>, changed: array<KB, VB>, unchanged: array<KB, VB>}
	 */
	public static function computeDifferencesInSecond(array $first, array $second): array {
		return [
			'new' => self::getEntriesMissingInSecond($second, $first),
			'missing' => self::getEntriesMissingInSecond($first, $second),
			'changed' => self::getDifferencesInCommonKeysFromSecond($first, $second),
			'unchanged' => self::getUnchangedEntries($first, $second),
		];
	}

	/**
	 * Returns the keys that are missing in $second but are present in $first.
	 *
	 * @template KA of array-key
	 * @template KB of array-key
	 * @template VA
	 * @template VB
	 * @param array<KA, VA> $first
	 * @param array<KB, VB> $second
	 * @return array{new: list<KB>, missing: list<KA>, changed: list<KB>, unchanged: list<KB>}
	 */
	public static function computeChangedKeysInSecond(array $first, array $second): array {
		$result = self::computeDifferencesInSecond($first, $second);
		return [
			'new' => array_keys($result['new']),
			'missing' => array_keys($result['missing']),
			'changed' => array_keys($result['changed']),
			'unchanged' => array_keys($result['unchanged']),
		];
	}

	/**
	 * Returns the keys that are missing in $second but are present in $first.
	 *
	 * @template KA of array-key
	 * @template KB of array-key
	 * @param array<KA, mixed> $first
	 * @param array<KB, mixed> $second
	 * @return list<KA>
	 */
	public static function getKeysMissingInSecondArray(array $first, array $second): array {
		return array_keys(self::getEntriesMissingInSecond($first, $second));
	}

	/**
	 * Returns keys and values that are missing in $second but are present in $first.
	 *
	 * @template KA of array-key
	 * @template KB of array-key
	 * @template VA
	 * @template VB
	 * @param array<KA, VA> $first
	 * @param array<KB, VB> $second
	 * @return array<KA, VA>
	 */
	public static function getEntriesMissingInSecond(array $first, array $second): array {
		return array_diff_key($first, $second);
	}

	/**
	 * @template KA of array-key
	 * @template KB of array-key
	 * @template VA
	 * @template VB
	 * @param array<KA, VA> $first
	 * @param array<KB, VB> $second
	 * @return array<KB, VB>
	 */
	public static function getDifferencesInCommonKeysFromSecond(array $first, array $second): array {
		$intersectingKeys = array_intersect_key($first, $second);
		$intersectingFirst = array_intersect_key($first, $intersectingKeys);
		$intersectingSecond = array_intersect_key($second, $intersectingKeys);
		return array_diff_assoc($intersectingSecond, $intersectingFirst);
	}

	/**
	 * @template KA of array-key
	 * @template KB of array-key
	 * @template VA
	 * @template VB
	 * @param array<KA, VA> $first
	 * @param array<KB, VB> $second
	 * @return array<KB, VB>
	 */
	public static function getUnchangedEntries(array $first, array $second) {
		$intersectingKeys = array_keys(array_intersect_key($first, $second));
		$intersectingKeys = array_combine($intersectingKeys, $intersectingKeys);
		$intersectingFirst = array_intersect_key($first, $intersectingKeys);
		$intersectingSecond = array_intersect_key($second, $intersectingKeys);
		return array_intersect_assoc($intersectingSecond, $intersectingFirst);
	}
}
