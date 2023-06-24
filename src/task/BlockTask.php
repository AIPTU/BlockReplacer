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

namespace aiptu\blockreplacer\task;

use aiptu\blockreplacer\BlockReplacer;
use pocketmine\scheduler\Task;
use function time;

final class BlockTask extends Task
{
	public function onRun(): void
	{
		foreach (BlockReplacer::getInstance()->getBlocks() as $block) {
			$block->check();
			if ($block->getLastAccessTime() !== -1) {
				if (time() - $block->getLastAccessTime() >= 120 && $block->isRefilled()) {
					BlockReplacer::getInstance()->removeBlock($block);
				}
			}
		}
	}
}
