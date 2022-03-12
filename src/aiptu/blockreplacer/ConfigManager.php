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
use pocketmine\item\Item;
use pocketmine\utils\Config;
use function array_filter;
use function array_map;
use function count;
use function explode;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;
use function rename;
use function var_export;

final class ConfigManager
{
	private const CONFIG_VERSION = 1.5;

	private static Config $config;

	private static int $cooldown;
	private static bool $autoPickup;
	private static Item $defaultReplace;
	private static array $listBlocks;
	private static bool $enableWorldBlacklist;
	private static array $blacklistedWorlds;
	private static bool $enableWorldWhitelist;
	private static array $whitelistedWorlds;

	public static function init(BlockReplacer $plugin): void
	{
		$plugin->saveDefaultConfig();

		if (!$plugin->getConfig()->exists('config-version') || ($plugin->getConfig()->get('config-version', self::CONFIG_VERSION) !== self::CONFIG_VERSION)) {
			$plugin->getLogger()->warning('An outdated config was provided attempting to generate a new one...');
			if (!rename($plugin->getDataFolder() . 'config.yml', $plugin->getDataFolder() . 'config.old.yml')) {
				$plugin->getLogger()->critical('An unknown error occurred while attempting to generate the new config');
				$plugin->getServer()->getPluginManager()->disablePlugin($plugin);
			}
			$plugin->reloadConfig();
		}

		self::$config = $plugin->getConfig();

		self::$cooldown = self::getInt('cooldown', 60);
		self::$autoPickup = self::getBool('auto-pickup', true);
		self::$defaultReplace = $plugin->checkItem(self::getString('default-replace', 'bedrock'));
		self::$listBlocks = array_map(static function (string $item) use ($plugin): array {
			$explode = explode('=', $item);
			return (count($explode) === 2) ? [$plugin->checkItem($explode[0]), $plugin->checkItem($explode[1])] : [$plugin->checkItem($item), null];
		}, self::getStringList('list-blocks', []));
		self::$enableWorldBlacklist = self::getBool('enable-world-blacklist', false);
		self::$blacklistedWorlds = self::getStringList('blacklisted-worlds', []);
		self::$enableWorldWhitelist = self::getBool('enable-world-whitelist', false);
		self::$whitelistedWorlds = self::getStringList('whitelisted-worlds', []);
	}

	public static function isAutoPickupEnable(): bool
	{
		return self::$autoPickup;
	}

	public static function getCooldown(): int
	{
		return 20 * self::$cooldown;
	}

	public static function getDefaultReplace(): Item
	{
		return self::$defaultReplace;
	}

	public static function getListBlocks(): array
	{
		return self::$listBlocks;
	}

	public static function isWorldBlacklistEnable(): bool
	{
		return self::$enableWorldBlacklist;
	}

	public static function getBlacklistedWorlds(): array
	{
		return self::$blacklistedWorlds;
	}

	public static function isWorldWhitelistEnable(): bool
	{
		return self::$enableWorldWhitelist;
	}

	public static function getWhitelistedWorlds(): array
	{
		return self::$whitelistedWorlds;
	}

	private static function getBool(string $key, bool $default): bool
	{
		$value = self::$config->getNested($key, $default);
		if (!is_bool($value)) {
			throw new InvalidArgumentException("Invalid config value for {$key}: " . self::printValue($value) . ', expected bool');
		}
		return $value;
	}

	private static function getInt(string $key, int $default): int
	{
		$value = self::$config->getNested($key, $default);
		if (!is_int($value)) {
			throw new InvalidArgumentException("Invalid config value for {$key}: " . self::printValue($value) . ', expected integer');
		}
		return $value;
	}

	private static function getString(string $key, string $default): string
	{
		$value = self::$config->getNested($key, $default);
		if (!is_string($value)) {
			throw new InvalidArgumentException("Invalid config value for {$key}: " . self::printValue($value) . ', expected string');
		}
		return $value;
	}

	/**
	 * @param string[] $default
	 *
	 * @return string[]
	 */
	private static function getStringList(string $key, array $default): array
	{
		$value = self::$config->getNested($key, $default);
		if (!is_array($value) || array_filter($value, 'is_string') !== $value) {
			throw new InvalidArgumentException("Invalid config value for {$key}: " . self::printValue($value) . ', expected string array');
		}
		return $value;
	}

	private static function printValue(mixed $value): string
	{
		return var_export($value, true);
	}
}
