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

namespace aiptu\blockreplacer\notification;

use aiptu\blockreplacer\BlockReplacer;
use aiptu\blockreplacer\config\NotificationConfiguration;
use aiptu\blockreplacer\data\BlockData;
use pocketmine\math\AxisAlignedBB;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function str_replace;
use function time;

class NotificationManager {
	public const NOTIFICATION_ACTIONBAR = 0;
	public const NOTIFICATION_TITLE = 1;
	public const NOTIFICATION_SUBTITLE = 2;
	public const NOTIFICATION_POPUP = 3;

	/**
	 * Send a notification about block replacement.
	 */
	public static function sendBlockReplaceNotification(Player $player, BlockData $blockData) : void {
		$config = BlockReplacer::getInstance()->getConfiguration()->getNotification();

		if (!$config->isEnabled() || !$config->showOnReplace()) {
			return;
		}

		$message = $config->getMessage('replace');
		$message = str_replace([
			'{from}',
			'{to}',
			'{time}',
		], [
			$blockData->getReplacedBlock()->getName(),
			$blockData->getReplacementBlock()->getName(),
			(string) $blockData->getRestoreDuration(),
		], $message);

		self::sendFormattedNotification($player, $message, $config, $blockData);
	}

	/**
	 * Send a notification about block restoration countdown.
	 */
	public static function sendRestoreCountdownNotification(BlockData $blockData) : void {
		$config = BlockReplacer::getInstance()->getConfiguration()->getNotification();

		if (!$config->isEnabled() || !$config->showCountdown()) {
			return;
		}

		$currentTime = time();
		$lastAccessTime = $blockData->getLastAccessTime();
		$duration = $blockData->getRestoreDuration();

		if ($lastAccessTime === -1) {
			return;
		}

		$elapsedTime = $currentTime - $lastAccessTime;
		$remainingTime = $duration - $elapsedTime;

		if ($remainingTime > 0) {
			self::sendCountdownNotification($blockData, $remainingTime);
		}
	}

	/**
	 * Send a notification about block restoration.
	 */
	public static function sendBlockRestoreNotification(BlockData $blockData) : void {
		$config = BlockReplacer::getInstance()->getConfiguration()->getNotification();

		if (!$config->isEnabled() || !$config->showOnRestore()) {
			return;
		}

		$message = $config->getMessage('restore');
		$message = str_replace('{block}', $blockData->getReplacedBlock()->getName(), $message);

		self::sendFormattedAreaNotification($message, $config, $blockData);
	}

	/**
	 * Send countdown notification with specific remaining time.
	 */
	public static function sendCountdownNotification(BlockData $blockData, int $remainingTime) : void {
		$config = BlockReplacer::getInstance()->getConfiguration()->getNotification();

		if (!$config->isEnabled() || !$config->showCountdown()) {
			return;
		}

		if ($blockData->getLastCountdownSent() === $remainingTime) {
			return;
		}

		$blockData->setLastCountdownSent($remainingTime);

		$message = $config->getMessage('countdown');
		$message = str_replace([
			'{block}',
			'{time}',
		], [
			$blockData->getReplacedBlock()->getName(),
			(string) $remainingTime,
		], $message);

		self::sendFormattedAreaNotification($message, $config, $blockData);
	}

	/**
	 * Send formatted notification to a specific player.
	 */
	private static function sendFormattedNotification(Player $player, string $message, NotificationConfiguration $config, BlockData $blockData) : void {
		$message = TextFormat::colorize($message);
		$type = self::getNotificationType($config->getType());

		if ($config->getNotificationRadius() > 0) {
			self::sendAreaNotification($blockData, $message, $type, $config->getNotificationRadius());
		} else {
			self::sendNotification($player, $message, $type);
		}
	}

	/**
	 * Send formatted notification to area.
	 */
	private static function sendFormattedAreaNotification(string $message, NotificationConfiguration $config, BlockData $blockData) : void {
		$message = TextFormat::colorize($message);
		$type = self::getNotificationType($config->getType());
		$radius = $config->getNotificationRadius();
		$blockReplacer = BlockReplacer::getInstance();

		if ($radius > 0) {
			self::sendAreaNotification($blockData, $message, $type, $radius);
		} else {
			$blockBreakerName = $blockData->getBlockBreaker();
			if ($blockBreakerName !== null) {
				$player = $blockReplacer->getServer()->getPlayerExact($blockBreakerName);
				if ($player instanceof Player) {
					self::sendNotification($player, $message, $type);
				}
			}
		}
	}

	/**
	 * Get notification type constant from string.
	 */
	private static function getNotificationType(string $typeString) : int {
		return match ($typeString) {
			'title' => self::NOTIFICATION_TITLE,
			'subtitle' => self::NOTIFICATION_SUBTITLE,
			'popup' => self::NOTIFICATION_POPUP,
			default => self::NOTIFICATION_ACTIONBAR,
		};
	}

	/**
	 * Send notification to player based on type.
	 */
	public static function sendNotification(Player $player, string $message, int $type) : void {
		switch ($type) {
			case self::NOTIFICATION_ACTIONBAR:
				$player->sendActionBarMessage($message);
				break;
			case self::NOTIFICATION_TITLE:
				$player->sendTitle($message, '', 10, 40, 10);
				break;
			case self::NOTIFICATION_SUBTITLE:
				$player->sendTitle('', $message, 10, 40, 10);
				break;
			case self::NOTIFICATION_POPUP:
				$player->sendPopup($message);
				break;
		}
	}

	/**
	 * Send notification to all players near a position.
	 */
	public static function sendAreaNotification(BlockData $blockData, string $message, int $type, int $radius) : void {
		$position = $blockData->getPosition();
		$world = $position->getWorld();

		$bb = new AxisAlignedBB(
			$position->x - $radius,
			$position->y - $radius,
			$position->z - $radius,
			$position->x + $radius,
			$position->y + $radius,
			$position->z + $radius
		);

		foreach ($world->getNearbyEntities($bb) as $entity) {
			if ($entity instanceof Player) {
				self::sendNotification($entity, $message, $type);
			}
		}
	}
}