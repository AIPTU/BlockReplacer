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

namespace aiptu\blockreplacer\data;

use aiptu\blockreplacer\BlockReplacer;
use aiptu\blockreplacer\event\BlockRefillEvent;
use aiptu\blockreplacer\event\BlockReplaceEvent;
use pocketmine\block\Block;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\Position;
use function time;

class BlockData {
	private bool $isRestored = true;
	private int $lastAccessTime = -1;
	private int $lastCountdownSent = -1;
	private ?string $blockBreaker = null;

	public function __construct(
		private readonly Position $position,
		private readonly Block $replacedBlock,
		private readonly Block $replacementBlock,
		private readonly int $restoreDuration
	) {}

	/**
	 * Get the position of the block.
	 */
	public function getPosition() : Position {
		return $this->position;
	}

	/**
	 * Get the replaced block.
	 */
	public function getReplacedBlock() : Block {
		return $this->replacedBlock;
	}

	/**
	 * Get the replacement block.
	 */
	public function getReplacementBlock() : Block {
		return $this->replacementBlock;
	}

	/**
	 * Set the last access time of the block.
	 */
	public function setLastAccessTime(int $lastAccessTime) : void {
		$this->lastAccessTime = $lastAccessTime;
	}

	/**
	 * Get the last access time of the block.
	 */
	public function getLastAccessTime() : int {
		return $this->lastAccessTime;
	}

	/**
	 * Get the restore duration of the block.
	 */
	public function getRestoreDuration() : int {
		return $this->restoreDuration;
	}

	/**
	 * Check if the block has been restored.
	 */
	public function isRestored() : bool {
		return $this->isRestored;
	}

	/**
	 * Set the restore status of the block.
	 */
	public function setRestored(bool $isRestored) : void {
		$this->isRestored = $isRestored;

		if ($isRestored) {
			$this->lastCountdownSent = -1;
		}
	}

	/**
	 * Get the last countdown number that was sent.
	 */
	public function getLastCountdownSent() : int {
		return $this->lastCountdownSent;
	}

	/**
	 * Set the last countdown number that was sent.
	 */
	public function setLastCountdownSent(int $countdown) : void {
		$this->lastCountdownSent = $countdown;
	}

	/**
	 * Set the player who broke the block.
	 */
	public function setBlockBreaker(?Player $player) : void {
		$this->blockBreaker = $player !== null ? $player->getName() : null;
	}

	/**
	 * Get the name of the player who broke the block.
	 */
	public function getBlockBreaker() : ?string {
		return $this->blockBreaker;
	}

	/**
	 * Replace the block with the replacement block.
	 */
	public function replaceBlock(Player $player) : void {
		$blockReplacer = BlockReplacer::getInstance();

		if (!$this->isRestored) {
			return;
		}

		$this->setLastAccessTime(time());
		$this->setBlockBreaker($player);

		$ev = new BlockReplaceEvent($player, $this->getReplacementBlock(), $this->getPosition());
		$ev->call();
		if ($ev->isCancelled()) {
			return;
		}

		$block = $ev->getBlock();
		$position = $ev->getPosition();
		$world = $position->getWorld();

		$blockReplacer->getScheduler()->scheduleTask(new ClosureTask(static function () use ($block, $position, $world, $blockReplacer) : void {
			$world->setBlock($position, $block);
			$blockReplacer->getConfiguration()->getParticle()->addFrom($world, $position);
			$blockReplacer->getConfiguration()->getSound()->addFrom($world, $position);
		}));

		$this->setRestored(false);
	}

	/**
	 * Restore the block with the replaced block.
	 */
	public function restoreBlock() : void {
		$blockReplacer = BlockReplacer::getInstance();

		$ev = new BlockRefillEvent($this->getReplacedBlock(), $this->getPosition());
		$ev->call();
		if ($ev->isCancelled()) {
			return;
		}

		$block = $ev->getBlock();
		$position = $ev->getPosition();
		$world = $position->getWorld();

		$world->setBlock($position, $block);
		$blockReplacer->getConfiguration()->getParticle()->addTo($world, $position);
		$blockReplacer->getConfiguration()->getSound()->addTo($world, $position);

		$this->setRestored(true);
	}

	/**
	 * Check if the block needs to be restored based on the access time and restore duration.
	 */
	public function checkRestoreStatus() : void {
		$lastAccessTime = $this->getLastAccessTime();
		$isRestored = $this->isRestored();

		if ($lastAccessTime !== -1 && !$isRestored) {
			$currentTime = time();
			$elapsedTime = $currentTime - $lastAccessTime;
			$time = $this->getRestoreDuration();

			if ($elapsedTime >= $time) {
				$this->restoreBlock();
			}
		}
	}
}