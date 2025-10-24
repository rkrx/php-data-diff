<?php

namespace DataDiff;

class DiffKeyValue {
	/**
	 * Returns the keys that are missing in $second but are present in $first.
	 *
	 * @template KA
	 * @template KB
	 * @param array<KA, mixed> $first
	 * @param array<KB, mixed> $second
	 * @return list<KA|KB>
	 */
	public static function getKeysMissingInSecondArray(array $first, array $second): array {
		return array_keys(array_diff_key($first, $second));
	}

	/**
	 * @template KA
	 * @template KB
	 * @template VA
	 * @template VB
	 * @param array<KA, VA> $first
	 * @param array<KB, VB> $second
	 * @return array<KA|KB, VA|VB>
	 */
	public static function getDifferencesInCommonKeysFromSecond(array $first, array $second): array {
		$intersectingKeys = array_keys(array_intersect_key($first, $second));
		$intersectingKeys = array_combine($intersectingKeys, $intersectingKeys);
		$intersectingFirst = array_intersect_key($first, $intersectingKeys);
		$intersectingSecond = array_intersect_key($second, $intersectingKeys);
		return array_diff_assoc($intersectingSecond, $intersectingFirst);
	}
}
