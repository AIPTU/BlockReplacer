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

use aiptu\blockreplacer\utils\BlockUtils;
use aiptu\blockreplacer\utils\ConfigHandler;
use JackMD\UpdateNotifier\UpdateNotifier;
use pocketmine\block\Block;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;
use function class_exists;
use function in_array;

final class BlockReplacer extends PluginBase
{
	use SingletonTrait;

	public const PERMISSION = 'blockreplacer.bypass';
	public const PERMISSION_DESCRIPTION = 'Allows users to bypass block replacement';

	public function onEnable(): void
	{
		self::setInstance($this);

		ConfigHandler::getInstance();

		$this->getServer()->getPluginManager()->registerEvents(new EventHandler(), $this);

		$this->checkUpdate();
	}

	public function getBlock(string $string): Block
	{
		$block = BlockUtils::fromString($string);
		if ($block === null) {
			throw new \InvalidArgumentException('Unable to parse "' . $string . '" to a valid block');
		}

		return $block;
	}

	public function checkWorld(World $world): bool
	{
		$blacklist = ConfigHandler::getInstance()->isWorldBlacklistEnable();
		$whitelist = ConfigHandler::getInstance()->isWorldWhitelistEnable();
		$world_name = $world->getFolderName();

		if ($blacklist === $whitelist) {
			return true;
		}

		if ($blacklist) {
			$disallowed_worlds = ConfigHandler::getInstance()->getBlacklistedWorlds();
			return !in_array($world_name, $disallowed_worlds, true);
		}

		if ($whitelist) {
			$allowed_worlds = ConfigHandler::getInstance()->getWhitelistedWorlds();
			return in_array($world_name, $allowed_worlds, true);
		}

		return false;
	}

	public function setBlock(World $world, Block $block_from, Block $block_to): void
	{
		$x = $block_from->getPosition()->getFloorX();
		$z = $block_from->getPosition()->getFloorZ();

		$world->orderChunkPopulation($x >> Chunk::COORD_BIT_SIZE, $z >> Chunk::COORD_BIT_SIZE, null)->onCompletion(
			static function (Chunk $chunk) use ($block_from, $block_to, $world): void {
				$world->setBlock($block_from->getPosition(), $block_to);
				$world->getLogger()->debug('Replacing the block from "' . $block_from->getName() . '" to "' . $block_to->getName() . '"');
			},
			static function () use ($block_from, $block_to, $world): void {
				$world->getLogger()->error('An error that occurred while replacing the block from "' . $block_from->getName() . '" to "' . $block_to->getName() . '"');
			},
		);
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
