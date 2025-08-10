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

namespace aiptu\blockreplacer\task;

use aiptu\blockreplacer\BlockReplacer;
use aiptu\blockreplacer\data\BlockDataManager;
use aiptu\blockreplacer\notification\NotificationManager;
use pocketmine\scheduler\Task;
use function time;

class TaskHandler extends Task {
	public function onRun() : void {
		$blockDataManager = BlockDataManager::getInstance();
		$blockReplacer = BlockReplacer::getInstance();
		$currentTime = time();

		foreach ($blockDataManager->getBlockDataList() as $blockData) {
			$wasRestored = $blockData->isRestored();

			$blockData->checkRestoreStatus();

			if (!$wasRestored && $blockData->isRestored()) {
				NotificationManager::sendBlockRestoreNotification($blockData);
				$blockData->setBlockBreaker(null);
			}

			if (!$blockData->isRestored() && $blockData->getLastAccessTime() !== -1) {
				$elapsedTime = $currentTime - $blockData->getLastAccessTime();
				$remainingTime = $blockData->getRestoreDuration() - $elapsedTime;

				$notificationConfig = $blockReplacer->getConfiguration()->getNotification();
				$countdownStart = $notificationConfig->getCountdownStart();

				if ($remainingTime > 0 && $remainingTime <= $countdownStart) {
					NotificationManager::sendCountdownNotification($blockData, $remainingTime);
				}
			}

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