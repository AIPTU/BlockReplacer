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
use function count;
use function explode;

final class EventHandler implements Listener
{
	public function __construct(private BlockReplacer $plugin)
	{
	}

	public function getPlugin(): BlockReplacer
	{
		return $this->plugin;
	}

	public function onBlockBreak(BlockBreakEvent $event): void
	{
		$block = $event->getBlock();
		$player = $event->getPlayer();
		$world = $block->getPosition()->getWorld();

		if (!$block->getBreakInfo()->isToolCompatible($event->getItem())) {
			return;
		}

		$defaultBlock = $this->getPlugin()->checkItem($this->getPlugin()->getConfigProperty()->getPropertyString('blocks.default-replace', 'minecraft:bedrock'));
		$fromBlock = null;
		$toBlock = null;

		foreach ($this->getPlugin()->getConfigProperty()->getPropertyArray('blocks.list', []) as $value) {
			$explode = explode('=', $value);

			if (count($explode) === 1) {
				$fromBlock = $this->getPlugin()->checkItem($value);
			} elseif (count($explode) === 2) {
				$fromBlock = $this->getPlugin()->checkItem($explode[0]);
				$toBlock = $this->getPlugin()->checkItem($explode[1]);
			}

			if ($fromBlock === null) {
				continue;
			}

			if ($block->asItem()->equals($fromBlock)) {
				if (!$player->hasPermission($this->getPlugin()->checkPermission()) && !$this->getPlugin()->checkWorld($world)) {
					return;
				}

				foreach ($event->getDrops() as $drops) {
					if ($this->getPlugin()->getConfigProperty()->getPropertyBool('auto-pickup', true)) {
						(!$player->getInventory()->canAddItem($drops)) ? ($world->dropItem($block->getPosition(), $drops)) : ($player->getInventory()->addItem($drops));
						(!$player->getXpManager()->canPickupXp()) ? ($world->dropExperience($block->getPosition(), $event->getXpDropAmount())) : ($player->getXpManager()->addXp($event->getXpDropAmount()));

						continue;
					}

					$world->dropItem($block->getPosition(), $drops);
					$world->dropExperience($block->getPosition(), $event->getXpDropAmount());
				}

				$event->cancel();

				$world->setBlock($block->getPosition(), $defaultBlock->getBlock());

				if ($toBlock === null) {
					$world->setBlock($block->getPosition(), $defaultBlock->getBlock());
				} else {
					$world->setBlock($block->getPosition(), $toBlock->getBlock());
				}

				$this->getPlugin()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($block, $world): void {
					$world->setBlock($block->getPosition(), $block);
				}), 20 * $this->getPlugin()->getConfigProperty()->getPropertyInt('cooldown', 60));
			}
		}
	}
}
