<?php

declare(strict_types=1);

namespace aiptu\blockreplacer\utils;

use pocketmine\utils\Config;
use function array_key_exists;
use function getopt;

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

	public function getProperty(string $variable, mixed $defaultValue = null): mixed
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

	public function getPropertyBool(string $variable, bool $defaultValue): bool
	{
		return (bool) $this->getProperty($variable, $defaultValue);
	}

	public function getPropertyInt(string $variable, int $defaultValue): int
	{
		return (int) $this->getProperty($variable, $defaultValue);
	}

	public function getPropertyString(string $variable, string $defaultValue): string
	{
		return (string) $this->getProperty($variable, $defaultValue);
	}

	public function save(): void
	{
		if ($this->getConfig()->hasChanged()) {
			$this->getConfig()->save();
		}
	}
}
