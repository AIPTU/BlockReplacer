<?php

declare(strict_types=1);

namespace aiptu\blockreplacer;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\item\LegacyStringToItemParser;
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

		$defaultBlock = LegacyStringToItemParser::getInstance()->parse($this->getPlugin()->getConfigProperty()->getPropertyString('blocks.default-replace', 'minecraft:bedrock'));
		$fromBlock = null;
		$toBlock = null;

		foreach ($this->getPlugin()->getConfigProperty()->getProperty('blocks.list', []) as $value) {
			$explode = explode('=', $value);

			if (count($explode) === 1) {
				$fromBlock = LegacyStringToItemParser::getInstance()->parse($value);
			} elseif (count($explode) === 2) {
				$fromBlock = LegacyStringToItemParser::getInstance()->parse($explode[0]);
				$toBlock = LegacyStringToItemParser::getInstance()->parse($explode[1]);
			}

			if ($fromBlock === null) {
				continue;
			}

			if ($block->getId() === $fromBlock->getId() && $block->getMeta() === $fromBlock->getMeta()) {
				if (!$player->hasPermission($this->getPlugin()->checkPermission()) && !$this->getPlugin()->checkWorlds($world)) {
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
