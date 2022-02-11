<?php

/*
 *
 * Copyright (c) 2021 AIPTU
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 */

declare(strict_types=1);

namespace aiptu\blockreplacer;

use InvalidArgumentException;
use pocketmine\utils\Config;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;
use function var_export;

final class TypedConfig
{
	public function __construct(private Config $config)
	{
	}

	public function getBool(string $key, bool $default = true): bool
	{
		$value = $this->config->getNested($key, $default);
		if (!is_bool($value)) {
			throw new InvalidArgumentException("Invalid value for {$key}: " . self::printValue($value));
		}
		return $value;
	}

	public function getInt(string $key, int $default = 0): int
	{
		$value = $this->config->getNested($key, $default);
		if (!is_int($value)) {
			throw new InvalidArgumentException("Invalid value for {$key}: " . self::printValue($value));
		}
		return $value;
	}

	public function getString(string $key, string $default = ''): string
	{
		$value = $this->config->getNested($key, $default);
		if (!is_string($value)) {
			throw new InvalidArgumentException("Invalid value for {$key}: " . self::printValue($value));
		}
		return $value;
	}

	/**
	 * @param string[] $default
	 *
	 * @return string[]
	 */
	public function getStringList(string $key, array $default = []): array
	{
		$value = $this->config->getNested($key, $default);
		if (!is_array($value)) {
			throw new InvalidArgumentException("Invalid value for {$key}: " . self::printValue($value));
		}
		return $value;
	}

	private static function printValue(mixed $value): string
	{
		return var_export($value, true);
	}
}
