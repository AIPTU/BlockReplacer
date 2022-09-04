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

use pocketmine\block\Block;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\network\mcpe\protocol\SpawnParticleEffectPacket;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\format\Chunk;

final class EventHandler implements Listener
{
	/**
	 * @handleCancelled true
	 * @priority HIGH
	 */
	public function onBlockBreak(BlockBreakEvent $event): void
	{
		$block = $event->getBlock();
		$player = $event->getPlayer();
		$world = $block->getPosition()->getWorld();
		$defaultBlock = ConfigManager::getInstance()->getDefaultReplace();

		foreach (ConfigManager::getInstance()->getListBlocks() as $data) {
			[$fromBlock, $toBlock] = $data;

			if ($block->asItem()->equals($fromBlock, true, false)) {
				if ($player->hasPermission(BlockReplacer::PERMISSION)) {
					if (!BlockReplacer::getInstance()->checkWorld($player->getWorld())) {
						return;
					}

					foreach ($event->getDrops() as $drops) {
						if (ConfigManager::getInstance()->isAutoPickupEnable()) {
							(!$player->getInventory()->canAddItem($drops)) ? ($world->dropItem($block->getPosition()->add(0.5, 0.5, 0.5), $drops)) : ($player->getInventory()->addItem($drops));
							(!$player->getXpManager()->canPickupXp()) ? ($world->dropExperience($block->getPosition()->add(0.5, 0.5, 0.5), $event->getXpDropAmount())) : ($player->getXpManager()->addXp($event->getXpDropAmount()));

							continue;
						}

						$world->dropItem($block->getPosition()->add(0.5, 0.5, 0.5), $drops);
						$world->dropExperience($block->getPosition()->add(0.5, 0.5, 0.5), $event->getXpDropAmount());
					}

					$event->cancel();

					$particleFrom = ConfigManager::getInstance()->getParticleFrom();
					if ($particleFrom !== null) {
						BlockReplacer::getInstance()->getServer()->broadcastPackets($world->getPlayers(), [
							SpawnParticleEffectPacket::create(DimensionIds::OVERWORLD, -1, $block->getPosition()->up(), $particleFrom, null),
						]);
					}
					$soundFrom = ConfigManager::getInstance()->getSoundFrom();
					if ($soundFrom !== null) {
						$world->addSound($block->getPosition(), $soundFrom);
					}

					if ($toBlock === null) {
						self::setBlock($block, $defaultBlock->getBlock());
					} else {
						self::setBlock($block, $toBlock->getBlock());
					}

					BlockReplacer::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(static function () use ($block, $world): void {
						self::setBlock($block, $block);
						$particleTo = ConfigManager::getInstance()->getParticleTo();
						if ($particleTo !== null) {
							BlockReplacer::getInstance()->getServer()->broadcastPackets($world->getPlayers(), [
								SpawnParticleEffectPacket::create(DimensionIds::OVERWORLD, -1, $block->getPosition()->up(), $particleTo, null),
							]);
						}
						$soundTo = ConfigManager::getInstance()->getSoundTo();
						if ($soundTo !== null) {
							$world->addSound($block->getPosition(), $soundTo);
						}
					}), ConfigManager::getInstance()->getCooldown());
				}
			}
		}
	}

	private static function setBlock(Block $fromBlock, Block $toBlock): void
	{
		$world = $fromBlock->getPosition()->getWorld();
		$x = $fromBlock->getPosition()->getFloorX();
		$z = $fromBlock->getPosition()->getFloorZ();

		$world->orderChunkPopulation($x >> Chunk::COORD_BIT_SIZE, $z >> Chunk::COORD_BIT_SIZE, null)->onCompletion(
			static function (Chunk $chunk) use ($fromBlock, $toBlock, $world): void {
				$world->setBlock($fromBlock->getPosition(), $toBlock);
				$world->getLogger()->debug('Replacing the block from "' . $fromBlock->getName() . '" to "' . $toBlock->getName() . '"');
			},
			static function () use ($world): void {
				$world->getLogger()->error('An error that occurred while replacing the block');
			},
		);
	}
}
