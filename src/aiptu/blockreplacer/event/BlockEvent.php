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

namespace aiptu\blockreplacer\event;

use pocketmine\block\Block;
use pocketmine\event\Event;
use pocketmine\utils\Utils;
use pocketmine\world\Position;

abstract class BlockEvent extends Event {
	public function __construct(
		protected Block $block,
		protected Position $position,
	) {}

	public function getBlock() : Block {
		return $this->block;
	}

	public function setBlock(Block $block) : void {
		$this->block = $block;
	}

	public function getPosition() : Position {
		return $this->position;
	}

	public function setPosition(Position $position) : void {
		if (!$position->isValid()) {
			throw new \InvalidArgumentException('Spawn position must reference a valid and loaded World');
		}

		Utils::checkVector3NotInfOrNaN($position);
		$this->position = $position;
	}
}