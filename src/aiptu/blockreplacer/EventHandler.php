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
use aiptu\blockreplacer\utils\ItemParser;
use aiptu\blockreplacer\utils\Utils;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\world\World;
use function array_map;
use function count;
use function explode;

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
		$defaultReplace = $blockConfig->getDefaultReplace();
		$defaultTime = $blockConfig->getDefaultTime();
		$blockDataManager = BlockDataManager::getInstance();

		foreach ($blockConfig->getListBlocks() as $data => $v) {
			[$previousBlock, $nextBlock, $time] = self::parseBlockData($data, $defaultReplace, $defaultTime);

			if ($block->isSameState($previousBlock) && !self::shouldCancelEvent($player, $world, $blockReplacer)) {
				$blockData = $blockDataManager->getBlockData($position);
				if ($blockData === null) {
					$blockData = new BlockData($position, $previousBlock, $nextBlock, $time);
					$blockDataManager->addBlockData($blockData);
				}

				$blockData->replaceBlock($player);

				NotificationManager::sendBlockReplaceNotification($player, $blockData);

				if (isset($v['drops'])) {
					$drops = self::applyChanceToDrops($v['drops']);
					$event->setDrops($drops);
				}

				if (isset($v['experience'])) {
					$xp = self::applyChanceToExperience($v['experience']);
					$event->setXpDropAmount($xp);
				}

				if ($autoPickupConfig->isAutoPickupEnabled()) {
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

				break;
			}
		}
	}

	private static function shouldCancelEvent(Player $player, World $world, BlockReplacer $blockReplacer) : bool {
		return !$player->hasPermission(PermissionConfiguration::NAME) || !$blockReplacer->checkWorld($world);
	}

	private static function parseBlockData(string $data, string $defaultReplace, int $defaultTime) : array {
		$dataParts = array_map('trim', explode('=', $data, 3));
		$numParts = count($dataParts);

		$previousBlock = ItemParser::parseBlock($dataParts[0]);
		$nextBlock = $numParts >= 2 ? ItemParser::parseBlock($dataParts[1]) : ItemParser::parseBlock($defaultReplace);
		$time = $numParts >= 3 ? Utils::parseAmount($dataParts[2]) : $defaultTime;

		return [
			$previousBlock,
			$nextBlock,
			$time,
		];
	}

	private static function applyChanceToDrops(array $drops) : array {
		$result = [];
		foreach ($drops as $drop) {
			if (isset($drop['item'], $drop['chance']) && Utils::checkChance($drop['chance'])) {
				$item = ItemParser::parseItem($drop);
				$result[] = $item;
			}
		}

		return $result;
	}

	private static function applyChanceToExperience(array $experience) : int {
		if (isset($experience['amount'], $experience['chance']) && Utils::checkChance($experience['chance'])) {
			return Utils::parseAmount($experience['amount']);
		}

		return 0;
	}
}