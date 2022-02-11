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
use function count;
use function explode;
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

	private TypedConfig $typedConfig;

	public function onEnable(): void
	{
		$this->checkConfig();

		$this->getServer()->getPluginManager()->registerEvents(new EventHandler($this), $this);

		$this->checkUpdate();
	}

	public function getTypedConfig(): TypedConfig
	{
		return $this->typedConfig;
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
			return !(in_array($world->getFolderName(), $this->getTypedConfig()->getStringList('worlds.list'), true));
		}

		return in_array($world->getFolderName(), $this->getTypedConfig()->getStringList('worlds.list'), true);
	}

	private function checkConfig(): void
	{
		$this->saveDefaultConfig();

		if (!$this->getConfig()->exists('config-version') || ($this->getConfig()->get('config-version', self::CONFIG_VERSION) !== self::CONFIG_VERSION)) {
			$this->getLogger()->warning('An outdated config was provided attempting to generate a new one...');
			if (!rename($this->getDataFolder() . 'config.yml', $this->getDataFolder() . 'config.old.yml')) {
				$this->getLogger()->critical('An unknown error occurred while attempting to generate the new config');
				$this->getServer()->getPluginManager()->disablePlugin($this);
			}
			$this->reloadConfig();
		}

		$this->typedConfig = new TypedConfig($this->getConfig());

		$this->checkItem($this->getTypedConfig()->getString('blocks.default-replace', 'minecraft:bedrock'));
		foreach ($this->getTypedConfig()->getStringList('blocks.list') as $value) {
			$explode = explode('=', $value);

			if (count($explode) === 1) {
				$fromBlock = $this->checkItem($value);
			} elseif (count($explode) === 2) {
				$fromBlock = $this->checkItem($explode[0]);
				$toBlock = $this->checkItem($explode[1]);
			}
		}

		match ($this->getTypedConfig()->getString('worlds.mode', 'blacklist')) {
			'blacklist' => $this->mode = self::MODE_BLACKLIST,
			'whitelist' => $this->mode = self::MODE_WHITELIST,
			default => throw new \InvalidArgumentException('Invalid mode selected, must be either "blacklist" or "whitelist"!'),
		};
	}

	private function checkUpdate(): void
	{
		if (!class_exists(UpdateNotifier::class)) {
			$this->getLogger()->error('UpdateNotifier virion not found. Download BlockReplacer at https://poggit.pmmp.io/p/BlockReplacer for a pre-compiled phar');
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}

		if ($this->getTypedConfig()->getBool('check-updates')) {
			UpdateNotifier::checkUpdate($this->getDescription()->getName(), $this->getDescription()->getVersion());
		}
	}
}
