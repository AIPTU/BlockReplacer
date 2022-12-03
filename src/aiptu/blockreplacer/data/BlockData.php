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

namespace aiptu\blockreplacer\data;

use aiptu\blockreplacer\BlockReplacer;
use aiptu\blockreplacer\event\BlockRefillEvent;
use aiptu\blockreplacer\event\BlockReplaceEvent;
use pocketmine\block\Block;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\Position;
use function time;

final class BlockData
{
	private bool $refilled = true;

	private int $lastAccessTime = -1;

	public function __construct(
		private Position $pos,
		private Block $previousBlock,
		private Block $nextBlock,
		private int $time,
	) {}

	public function getPosition(): Position
	{
		return $this->pos;
	}

	public function getPreviousBlock(): Block
	{
		return $this->previousBlock;
	}

	public function getNextBlock(): Block
	{
		return $this->nextBlock;
	}

	public function break(Player $player): void
	{
		if (!$this->refilled) {
			return;
		}
		$this->setAccessTime(time());
		$ev = new BlockReplaceEvent($player, $this->getNextBlock(), $this->pos);
		$ev->call();
		if ($ev->isCancelled()) {
			return;
		}
		$block = $ev->getBlock();
		$position = $ev->getPosition();
		$world = $position->getWorld();
		BlockReplacer::getInstance()->getScheduler()->scheduleTask(new ClosureTask(static function () use ($block, $position, $world): void {
			$world->setBlock($position, $block);
			BlockReplacer::getInstance()->getConfiguration()->getParticle()->addFrom($world, $position);
			BlockReplacer::getInstance()->getConfiguration()->getSound()->addFrom($world, $position);
		}));
		$this->setRefilled(false);
	}

	public function setAccessTime(int $time): void
	{
		$this->lastAccessTime = $time;
	}

	public function getLastAccessTime(): int
	{
		return $this->lastAccessTime;
	}

	public function refill(): void
	{
		$ev = new BlockRefillEvent($this->getPreviousBlock(), $this->pos);
		$ev->call();
		if ($ev->isCancelled()) {
			return;
		}
		$position = $ev->getPosition();
		$world = $position->getWorld();
		$world->setBlock($position, $ev->getBlock());
		BlockReplacer::getInstance()->getConfiguration()->getParticle()->addTo($world, $position);
		BlockReplacer::getInstance()->getConfiguration()->getSound()->addTo($world, $position);
		$this->setRefilled(true);
	}

	public function isRefilled(): bool
	{
		return $this->refilled;
	}

	public function setRefilled(bool $refilled): void
	{
		$this->refilled = $refilled;
	}

	public function check(): void
	{
		if ($this->lastAccessTime !== -1 && !$this->refilled) {
			if (time() - $this->lastAccessTime >= $this->time) {
				$this->refill();
			}
		}
	}

	public function getTime(): int
	{
		return $this->time;
	}
}
