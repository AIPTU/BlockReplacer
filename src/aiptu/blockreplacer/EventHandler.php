<?php

/*
 * Copyright (c) 2021-2025 AIPTU
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/AIPTU/BlockReplacer
 */

declare(strict_types=1);

namespace aiptu\blockreplacer;

use aiptu\blockreplacer\config\PermissionConfiguration;
use aiptu\blockreplacer\data\BlockData;
use aiptu\blockreplacer\data\BlockDataManager;
use aiptu\blockreplacer\notification\NotificationManager;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\World;

class EventHandler implements Listener {
	/**
	 * @priority HIGH
	 *
	 * @ignoreCancelled true
	 */
	public function onBlockBreak(BlockBreakEvent $event) : void {
		$block = $event->getBlock();
		$player = $event->getPlayer();
		$position = $block->getPosition();
		$world = $position->getWorld();

		$blockReplacer = BlockReplacer::getInstance();
		$blockConfig = $blockReplacer->getConfiguration()->getBlock();
		$autoPickupConfig = $blockReplacer->getConfiguration()->getAutoPickup();
		$blockDataManager = BlockDataManager::getInstance();

		if (self::shouldCancelEvent($player, $world, $blockReplacer)) {
			return;
		}

		$blockRule = $blockConfig->getBlockRule($block);
		if ($blockRule === null) {
			return;
		}

		$blockData = $blockDataManager->getBlockData($position);
		if ($blockData === null) {
			$blockData = new BlockData(
				$position,
				$blockRule->getFromBlock(),
				$blockRule->getToBlock(),
				$blockRule->getTime()
			);
			$blockDataManager->addBlockData($blockData);
		}

		$blockData->replaceBlock($player);

		NotificationManager::sendBlockReplaceNotification($player, $blockData);

		if ($blockRule->hasDropRules()) {
			$drops = $blockRule->generateDrops();
			$event->setDrops($drops);
		}

		if ($blockRule->hasExperienceRule()) {
			$xp = $blockRule->generateExperience();
			$event->setXpDropAmount($xp);
		}

		if ($autoPickupConfig->isAutoPickupEnabled()) {
			self::handleAutoPickup($player, $event, $position, $world);
		}
	}

	/**
	 * Check if the event should be cancelled.
	 */
	private static function shouldCancelEvent(Player $player, World $world, BlockReplacer $blockReplacer) : bool {
		return !$player->hasPermission(PermissionConfiguration::NAME) || !$blockReplacer->checkWorld($world);
	}

	/**
	 * Handle auto pickup functionality.
	 */
	private static function handleAutoPickup(Player $player, BlockBreakEvent $event, Vector3 $position, World $world) : void {
		$inventory = $player->getInventory();
		$drops = $event->getDrops();
		$xpAmount = $event->getXpDropAmount();

		foreach ($drops as $drop) {
			if ($inventory->canAddItem($drop)) {
				$inventory->addItem($drop);
			} else {
				$world->dropItem($position->add(0.5, 0.5, 0.5), $drop);
			}
		}

		$player->getXpManager()->addXp($xpAmount);

		$event->setDrops([]);
		$event->setXpDropAmount(0);
	}
}