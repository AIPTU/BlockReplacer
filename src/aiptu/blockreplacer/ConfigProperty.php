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

use pocketmine\utils\Config;
use function array_key_exists;
use function getopt;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;

final class ConfigProperty
{
	/**
	 * @var mixed[]
	 * @phpstan-var array<string, mixed>
	 */
	private array $propertyCache = [];

	public function __construct(private Config $config)
	{
	}

	public function getConfig(): Config
	{
		return $this->config;
	}

	public function getProperty(string $variable, mixed $defaultValue): mixed
	{
		if (!array_key_exists($variable, $this->propertyCache)) {
			$v = getopt('', ["{$variable}::"]);
			if (isset($v[$variable])) {
				$this->propertyCache[$variable] = $v[$variable];
			} else {
				$this->propertyCache[$variable] = $this->getConfig()->getNested($variable);
			}
		}

		return $this->propertyCache[$variable] ?? $defaultValue;
	}

	public function setProperty(string $variable, mixed $defaultValue): void
	{
		$this->getConfig()->setNested($variable, $defaultValue);
		$this->save();
		$this->propertyCache[$variable] = $this->getConfig()->getNested($variable);
	}

	public function getPropertyBool(string $variable, bool $defaultValue): bool
	{
		$value = $this->getProperty($variable, $defaultValue);
		if (!is_bool($value)) {
			$this->setProperty($variable, $defaultValue);
			return $defaultValue;
		}

		return $value;
	}

	public function getPropertyInt(string $variable, int $defaultValue): int
	{
		$value = $this->getProperty($variable, $defaultValue);
		if (!is_int($value)) {
			$this->setProperty($variable, $defaultValue);
			return $defaultValue;
		}

		return $value;
	}

	public function getPropertyFloat(string $variable, float $defaultValue): float
	{
		$value = $this->getProperty($variable, $defaultValue);
		if (!is_float($value)) {
			$this->setProperty($variable, $defaultValue);
			return $defaultValue;
		}

		return $value;
	}

	public function getPropertyString(string $variable, string $defaultValue): string
	{
		$value = $this->getProperty($variable, $defaultValue);
		if (!is_string($value)) {
			$this->setProperty($variable, $defaultValue);
			return $defaultValue;
		}

		return $value;
	}

	public function getPropertyArray(string $variable, array $defaultValue): array
	{
		$value = $this->getProperty($variable, $defaultValue);
		if (!is_array($value)) {
			$this->setProperty($variable, $defaultValue);
			return $defaultValue;
		}

		return $value;
	}

	public function save(): void
	{
		if ($this->getConfig()->hasChanged()) {
			$this->getConfig()->save();
		}
	}
}
