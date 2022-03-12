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
use pocketmine\plugin\PluginBase;
use pocketmine\world\World;
use function class_exists;
use function explode;
use function in_array;
use function str_replace;
use function trim;

final class BlockReplacer extends PluginBase
{
	public function onEnable(): void
	{
		ConfigManager::init($this);

		$this->getServer()->getPluginManager()->registerEvents(new EventHandler($this), $this);

		if (!class_exists(UpdateNotifier::class)) {
			$this->getLogger()->error('UpdateNotifier virion not found. Download BlockReplacer at https://poggit.pmmp.io/p/BlockReplacer');
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}
		UpdateNotifier::checkUpdate($this->getDescription()->getName(), $this->getDescription()->getVersion());
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
		$blacklist = ConfigManager::isWorldBlacklistEnable();
		$whitelist = ConfigManager::isWorldWhitelistEnable();
		$worldName = $world->getFolderName();

		if ($blacklist === $whitelist) {
			return true;
		}

		if ($blacklist) {
			$disallowedWorlds = ConfigManager::getBlacklistedWorlds();
			return !(in_array($worldName, $disallowedWorlds, true));
		}

		if ($whitelist) {
			$allowedWorlds = ConfigManager::getWhitelistedWorlds();
			return in_array($worldName, $allowedWorlds, true);
		}

		return false;
	}
}
