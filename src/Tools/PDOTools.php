<?php

namespace DataDiff\Tools;

use PDOStatement;
use RuntimeException;
use Throwable;

class PDOTools {
	/**
	 * @template T
	 * @param PDOStatement|false $stmt
	 * @param callable(PDOStatement): T $fn
	 * @return T
	 */
	public static function useStmt($stmt, $fn) {
		return self::testStmt($stmt, function (PDOStatement $stmt) use ($fn) {
			try {
				return $fn($stmt);
			} finally {
				try {
					$stmt->closeCursor();
				} catch(Throwable $e) {
				}
			}
		});
	}

	/**
	 * @template T
	 * @param PDOStatement|false $stmt
	 * @param callable(PDOStatement): T $fn
	 * @return T
	 */
	public static function testStmt($stmt, $fn) {
		if($stmt === false) {
			throw new RuntimeException('PDOStatement is null');
		}

		return $fn($stmt);
	}
}
