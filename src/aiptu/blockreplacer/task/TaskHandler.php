<?php

/*
 * Copyright (c) 2021-2024 AIPTU
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/AIPTU/BlockReplacer
 */

declare(strict_types=1);

namespace aiptu\blockreplacer\task;

use aiptu\blockreplacer\data\BlockDataManager;
use pocketmine\scheduler\Task;
use function time;

class TaskHandler extends Task {
	public function onRun() : void {
		$blockDataManager = BlockDataManager::getInstance();
		$currentTime = time();

		foreach ($blockDataManager->getBlockDataList() as $blockData) {
			$blockData->checkRestoreStatus();

			if ($blockData->isRestored() && $blockData->getLastAccessTime() !== -1) {
				$elapsedTime = $currentTime - $blockData->getLastAccessTime();
				$time = $blockData->getRestoreDuration();

				if ($elapsedTime >= $time) {
					$blockDataManager->removeBlockData($blockData);
				}
			}
		}
	}
}