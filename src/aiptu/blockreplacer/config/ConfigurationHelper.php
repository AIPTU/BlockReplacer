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

namespace aiptu\blockreplacer\config;

use function array_keys;
use function count;
use function gettype;
use function implode;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_scalar;
use function is_string;
use const PHP_INT_MAX;
use const PHP_INT_MIN;

class ConfigurationHelper {
	/**
	 * @param array<string, mixed> $data
	 */
	public static function read(array &$data, string $key) : mixed {
		if (!isset($data[$key])) {
			throw new BadConfigurationException("Cannot find required key '{$key}'");
		}

		$value = $data[$key];
		unset($data[$key]);
		return $value;
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function readOptional(array &$data, string $key, mixed $fallback) : mixed {
		try {
			return self::read($data, $key);
		} catch (BadConfigurationException) {
			return $fallback;
		}
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function readInt(array &$data, string $key, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX) : int {
		$value = self::read($data, $key);
		if (!is_int($value)) {
			throw new BadConfigurationException("Expected value of key '{$key}' to be an integer, got " . gettype($value) . (is_scalar($value) ? " ({$value})" : ''));
		}

		if ($value < $min || $value > $max) {
			throw new BadConfigurationException("Expected value of key '{$key}' to be between {$min} and {$max}, got {$value}");
		}

		return $value;
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function readNumber(array &$data, string $key) : float {
		$value = self::read($data, $key);
		if (!is_int($value) && !is_float($value)) {
			throw new BadConfigurationException("Expected value of key '{$key}' to be an number, got " . gettype($value) . (is_scalar($value) ? " ({$value})" : ''));
		}

		return (float) $value;
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function readBool(array &$data, string $key) : bool {
		$value = self::read($data, $key);
		if (!is_bool($value)) {
			throw new BadConfigurationException("Expected value of key '{$key}' to be a boolean, got " . gettype($value) . (is_scalar($value) ? " ({$value})" : ''));
		}

		return $value;
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function readString(array &$data, string $key) : string {
		$value = self::read($data, $key);
		if (!is_string($value)) {
			throw new BadConfigurationException("Expected value of key '{$key}' to be a string, got " . gettype($value) . (is_scalar($value) ? " ({$value})" : ''));
		}

		return $value;
	}

	/**
	 * @param array<string, mixed> $data
	 *
	 * @return array<string, mixed>
	 */
	public static function readMap(array &$data, string $key) : array {
		$value = self::read($data, $key);
		if (!is_array($value)) {
			throw new BadConfigurationException("Expected value of key '{$key}' to be a map, got " . gettype($value) . (is_scalar($value) ? " ({$value})" : ''));
		}

		return $value;
	}

	/**
	 * @param array<int|string, mixed> $data
	 */
	public static function checkForUnread(array $data) : void {
		$keys = array_keys($data);
		if (count($keys) > 0) {
			throw new BadConfigurationException("Unrecognized keys: '" . implode("', '", $keys) . "'");
		}
	}
}