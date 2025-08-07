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
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;
use pocketmine\world\Position;

class BlockReplaceEvent extends BlockEvent implements Cancellable {
	use CancellableTrait;

	public function __construct(
		protected Player $player,
		Block $block,
		Position $position,
	) {
		parent::__construct($block, $position);
	}

	public function getPlayer() : Player {
		return $this->player;
	}
}