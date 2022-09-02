<?php

/*
 * Copyright (c) 2021-2022 AIPTU
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/AIPTU/BlockReplacer
 */

declare(strict_types=1);

namespace aiptu\blockreplacer;

use JackMD\UpdateNotifier\UpdateNotifier;
use pocketmine\item\Item;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\item\LegacyStringToItemParserException;
use pocketmine\item\StringToItemParser;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\World;
use function class_exists;
use function in_array;

final class BlockReplacer extends PluginBase
{
	use SingletonTrait;

	public function onEnable(): void
	{
		self::setInstance($this);

		ConfigManager::getInstance();

		$this->getServer()->getPluginManager()->registerEvents(new EventHandler(), $this);

		$this->checkUpdate();
	}

	public function checkItem(string $string): Item
	{
		try {
			$item = StringToItemParser::getInstance()->parse($string) ?? LegacyStringToItemParser::getInstance()->parse($string);
		} catch (LegacyStringToItemParserException $e) {
			throw $e;
		}

		return $item;
	}

	public function checkWorld(World $world): bool
	{
		$blacklist = ConfigManager::getInstance()->isWorldBlacklistEnable();
		$whitelist = ConfigManager::getInstance()->isWorldWhitelistEnable();
		$worldName = $world->getFolderName();

		if ($blacklist === $whitelist) {
			return true;
		}

		if ($blacklist) {
			$disallowedWorlds = ConfigManager::getInstance()->getBlacklistedWorlds();
			return !in_array($worldName, $disallowedWorlds, true);
		}

		if ($whitelist) {
			$allowedWorlds = ConfigManager::getInstance()->getWhitelistedWorlds();
			return in_array($worldName, $allowedWorlds, true);
		}

		return false;
	}

	private function checkUpdate(): void
	{
		if (!class_exists(UpdateNotifier::class)) {
			$this->getLogger()->error('UpdateNotifier virion not found. Download BlockReplacer at https://poggit.pmmp.io/p/BlockReplacer');
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}
		UpdateNotifier::checkUpdate($this->getDescription()->getName(), $this->getDescription()->getVersion());
	}
}
