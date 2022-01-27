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

use JackMD\UpdateNotifier\UpdateNotifier;
use pocketmine\item\Item;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\item\LegacyStringToItemParserException;
use pocketmine\item\StringToItemParser;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\plugin\PluginBase;
use pocketmine\world\World;
use function array_keys;
use function class_exists;
use function count;
use function explode;
use function implode;
use function in_array;
use function rename;
use function str_replace;
use function trim;

final class BlockReplacer extends PluginBase
{
	private const CONFIG_VERSION = 1.3;

	private const MODE_BLACKLIST = 0;
	private const MODE_WHITELIST = 1;

	private int $mode;

	private ConfigProperty $configProperty;

	public function onEnable(): void
	{
		$this->getServer()->getPluginManager()->registerEvents(new EventHandler($this), $this);

		$this->checkConfig();

		$this->checkUpdate();
	}

	public function getConfigProperty(): ConfigProperty
	{
		return $this->configProperty;
	}

	public function checkItem(string $string): Item
	{
		try {
			$item = LegacyStringToItemParser::getInstance()->parse($string);
		} catch (LegacyStringToItemParserException $e) {
			if (($item = StringToItemParser::getInstance()->parse(explode(':', str_replace([' ', 'minecraft:'], ['_', ''], trim($string)))[0])) === null) {
				throw $e;
			}
		}

		return $item;
	}

	public function checkWorld(World $world): bool
	{
		if ($this->mode === self::MODE_BLACKLIST) {
			return !(in_array($world->getFolderName(), $this->getConfigProperty()->getPropertyArray('worlds.list', []), true));
		}

		return in_array($world->getFolderName(), $this->getConfigProperty()->getPropertyArray('worlds.list', []), true);
	}

	public function checkPermission(): string
	{
		$configProperty = $this->getConfigProperty();

		$permission = new Permission($configProperty->getPropertyString('permission.name', 'blockreplacer.bypass'), $configProperty->getPropertyString('permission.description', 'Allows users to bypass block replacement.'));
		$permission_manager = PermissionManager::getInstance();
		$permission_manager->addPermission($permission);
		$permission_default_register = [
			'op' => static function () use ($permission_manager, $permission): void {
				$permission_manager->getPermission(DefaultPermissions::ROOT_OPERATOR)?->addChild($permission->getName(), true);
			},
			'all' => static function () use ($permission_manager, $permission): void {
				$permission_manager->getPermission(DefaultPermissions::ROOT_USER)?->addChild($permission->getName(), true);
			},
		];

		if (isset($permission_default_register[$permission_defaults = $configProperty->getPropertyString('permission.defaults', 'op')])) {
			$permission_default_register[$permission_defaults]();
		} else {
			throw new \InvalidArgumentException("Invalid permission.defaults value configured: \"{$permission_defaults}\" (expected one of: " . implode(', ', array_keys($permission_default_register)) . ')');
		}

		return $permission->getName();
	}

	private function checkConfig(): void
	{
		$this->saveDefaultConfig();

		if (!$this->getConfig()->exists('config-version') || ($this->getConfig()->get('config-version', self::CONFIG_VERSION) !== self::CONFIG_VERSION)) {
			$this->getLogger()->notice('Your configuration file is outdated, updating the config.yml...');
			$this->getLogger()->notice('The old configuration file can be found at config.old.yml');

			rename($this->getDataFolder() . 'config.yml', $this->getDataFolder() . 'config.old.yml');

			$this->reloadConfig();
		}

		$this->configProperty = new ConfigProperty($this->getConfig());

		$this->checkItem($this->getConfigProperty()->getPropertyString('blocks.default-replace', 'minecraft:bedrock'));
		foreach ($this->getConfigProperty()->getPropertyArray('blocks.list', []) as $value) {
			$explode = explode('=', $value);

			if (count($explode) === 1) {
				$fromBlock = $this->checkItem($value);
			} elseif (count($explode) === 2) {
				$fromBlock = $this->checkItem($explode[0]);
				$toBlock = $this->checkItem($explode[1]);
			}
		}

		match ($this->getConfigProperty()->getPropertyString('worlds.mode', 'blacklist')) {
			'blacklist' => $this->mode = self::MODE_BLACKLIST,
			'whitelist' => $this->mode = self::MODE_WHITELIST,
			default => throw new \InvalidArgumentException('Invalid mode selected, must be either "blacklist" or "whitelist"!'),
		};

		$this->checkPermission();
	}

	private function checkUpdate(): void
	{
		if (!class_exists(UpdateNotifier::class)) {
			$this->getLogger()->error('UpdateNotifier virion not found. Download BlockReplacer at https://poggit.pmmp.io/p/BlockReplacer for a pre-compiled phar');
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}

		if ($this->getConfigProperty()->getPropertyBool('check-updates', true)) {
			UpdateNotifier::checkUpdate($this->getDescription()->getName(), $this->getDescription()->getVersion());
		}
	}
}
