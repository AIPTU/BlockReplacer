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

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\scheduler\ClosureTask;

final class EventHandler implements Listener
{
	public function __construct(private BlockReplacer $plugin)
	{
	}

	public function getPlugin(): BlockReplacer
	{
		return $this->plugin;
	}

	/**
	 * @handleCancelled true
	 * @priority HIGH
	 */
	public function onBlockBreak(BlockBreakEvent $event): void
	{
		$block = $event->getBlock();
		$player = $event->getPlayer();
		$world = $block->getPosition()->getWorld();
		$defaultBlock = ConfigManager::getDefaultReplace();

		foreach (ConfigManager::getListBlocks() as $data) {
			[$fromBlock, $toBlock] = $data;

			if ($block->asItem()->equals($fromBlock, true, false)) {
				if ($player->hasPermission('blockreplacer.bypass')) {
					if (!$this->getPlugin()->checkWorld($player->getWorld())) {
						return;
					}

					foreach ($event->getDrops() as $drops) {
						if (ConfigManager::isAutoPickupEnable()) {
							(!$player->getInventory()->canAddItem($drops)) ? ($world->dropItem($block->getPosition()->add(0.5, 0.5, 0.5), $drops)) : ($player->getInventory()->addItem($drops));
							(!$player->getXpManager()->canPickupXp()) ? ($world->dropExperience($block->getPosition()->add(0.5, 0.5, 0.5), $event->getXpDropAmount())) : ($player->getXpManager()->addXp($event->getXpDropAmount()));

							continue;
						}

						$world->dropItem($block->getPosition()->add(0.5, 0.5, 0.5), $drops);
						$world->dropExperience($block->getPosition()->add(0.5, 0.5, 0.5), $event->getXpDropAmount());
					}

					$event->cancel();

					if ($toBlock === null) {
						$world->setBlock($block->getPosition(), $defaultBlock->getBlock());
					} else {
						$world->setBlock($block->getPosition(), $toBlock->getBlock());
					}

					$this->getPlugin()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($block, $world): void {
						$world->setBlock($block->getPosition(), $block);
					}), ConfigManager::getCooldown());
				}
			}
		}
	}
}
