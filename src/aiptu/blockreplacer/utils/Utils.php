<?php

/*
 * Copyright (c) 2021-2025 AIPTU
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/AIPTU/BlockReplacer
 */

declare(strict_types=1);

namespace aiptu\blockreplacer\utils;

use pocketmine\world\Position;
use function explode;
use function implode;
use function is_int;
use function is_string;
use function mt_rand;

class Utils {
	/**
	 * Serializes a Position object into a string representation.
	 *
	 * @param Position $position the position object to be serialized
	 *
	 * @return string the serialized string representation of the position
	 */
	public static function serializePosition(Position $position) : string {
		return implode(':', [$position->getFloorX(), $position->getFloorY(), $position->getFloorZ(), $position->getWorld()->getFolderName()]);
	}

	/**
	 * Parses the item amount, which can be a single value or a range.
	 *
	 * @param mixed $amount the item amount or range
	 *
	 * @return int the parsed item amount
	 */
	public static function parseAmount($amount) : int {
		if (is_int($amount)) {
			return $amount;
		}

		if (is_string($amount)) {
			$range = explode('-', $amount);
			$min = isset($range[0]) ? (int) $range[0] : 1;
			$max = isset($range[1]) ? (int) $range[1] : $min;

			return mt_rand($min, $max);
		}

		return 1; // Default to 1 if amount is invalid
	}

	/**
	 * Checks if the chance condition is met.
	 *
	 * @param mixed $chance the chance value
	 *
	 * @return bool true if the chance is successful, false otherwise
	 */
	public static function checkChance($chance) : bool {
		$parsedChance = self::parseAmount($chance);

		return $parsedChance >= 100 || mt_rand(1, 100) <= $parsedChance;
	}
}