<?php

/*
 * Copyright (c) 2021-2023 AIPTU
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
use pocketmine\world\Position;

final class BlockRefillEvent extends BlockEvent implements Cancellable
{
	use CancellableTrait;

	public function __construct(Block $block, Position $position)
	{
		parent::__construct($block, $position);
	}
}
