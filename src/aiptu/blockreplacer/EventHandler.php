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
	 *
	 * @priority HIGH
	 */
	public function onBlockBreak(BlockBreakEvent $event): void
	{
		$block = $event->getBlock();
		$player = $event->getPlayer();
		$world = $block->getPosition()->getWorld();

		foreach (ConfigHandler::getInstance()->getBlocks() as $data) {
			[$block_from, $block_to, $time] = $data;

			if ($block->asItem()->equals($block_from, true, false)) {
				if ($player->hasPermission(BlockReplacer::PERMISSION)) {
					if (!BlockReplacer::getInstance()->checkWorld($player->getWorld())) {
						return;
					}

					foreach ($event->getDrops() as $drops) {
						if (ConfigHandler::getInstance()->isAutoPickupEnable()) {
							(!$player->getInventory()->canAddItem($drops)) ? ($world->dropItem($block->getPosition()->add(0.5, 0.5, 0.5), $drops)) : ($player->getInventory()->addItem($drops));
							(!$player->getXpManager()->canPickupXp()) ? ($world->dropExperience($block->getPosition()->add(0.5, 0.5, 0.5), $event->getXpDropAmount())) : ($player->getXpManager()->addXp($event->getXpDropAmount()));

							continue;
						}

						$world->dropItem($block->getPosition()->add(0.5, 0.5, 0.5), $drops);
						$world->dropExperience($block->getPosition()->add(0.5, 0.5, 0.5), $event->getXpDropAmount());
					}

					$event->cancel();

					$particle_from = ConfigHandler::getInstance()->getParticleFrom();
					if ($particle_from !== null) {
						BlockReplacer::getInstance()->getServer()->broadcastPackets($world->getPlayers(), [
							SpawnParticleEffectPacket::create(DimensionIds::OVERWORLD, -1, $block->getPosition()->up(), $particle_from, null),
						]);
					}

					$sound_from = ConfigHandler::getInstance()->getSoundFrom();
					if ($sound_from !== null) {
						$world->addSound($block->getPosition(), $sound_from);
					}

					if ($block_to === null) {
						self::setBlock($block, $block_from);
					} else {
						self::setBlock($block, $block_to);
					}

					BlockReplacer::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(static function () use ($block, $world): void {
						self::setBlock($block, $block);

						$particle_to = ConfigHandler::getInstance()->getParticleTo();
						if ($particle_to !== null) {
							BlockReplacer::getInstance()->getServer()->broadcastPackets($world->getPlayers(), [
								SpawnParticleEffectPacket::create(DimensionIds::OVERWORLD, -1, $block->getPosition()->up(), $particle_to, null),
							]);
						}

						$sound_to = ConfigHandler::getInstance()->getSoundTo();
						if ($sound_to !== null) {
							$world->addSound($block->getPosition(), $sound_to);
						}
					}), $time);
				}
			}
		}
	}

	private static function setBlock(Block $block_from, Block $block_to): void
	{
		$world = $block_from->getPosition()->getWorld();
		$x = $block_from->getPosition()->getFloorX();
		$z = $block_from->getPosition()->getFloorZ();

		$world->orderChunkPopulation($x >> Chunk::COORD_BIT_SIZE, $z >> Chunk::COORD_BIT_SIZE, null)->onCompletion(
			static function (Chunk $chunk) use ($block_from, $block_to, $world): void {
				$world->setBlock($block_from->getPosition(), $block_to);
				$world->getLogger()->debug('Replacing the block from "' . $block_from->getName() . '" to "' . $block_to->getName() . '"');
			},
			static function () use ($world): void {
				$world->getLogger()->error('An error that occurred while replacing the block');
			},
		);
	}
}
