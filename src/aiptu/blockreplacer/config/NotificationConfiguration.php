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

namespace aiptu\blockreplacer\config;

class NotificationConfiguration {
	public function __construct(
		private bool $enabled,
		private string $type,
		private bool $showOnReplace,
		private bool $showCountdown,
		private bool $showOnRestore,
		private int $countdownStart,
		private int $notificationRadius,
		private array $messages,
	) {}

	/**
	 * @param array<int|string, mixed> $data
	 */
	public static function fromData(array $data) : self {
		$instance = new self(
			ConfigurationHelper::readBool($data, 'enabled'),
			ConfigurationHelper::readString($data, 'type'),
			ConfigurationHelper::readBool($data, 'show-on-replace'),
			ConfigurationHelper::readBool($data, 'show-countdown'),
			ConfigurationHelper::readBool($data, 'show-on-restore'),
			ConfigurationHelper::readInt($data, 'countdown-start', 1, 60),
			ConfigurationHelper::readInt($data, 'notification-radius', 0),
			ConfigurationHelper::readMap($data, 'messages'),
		);
		ConfigurationHelper::checkForUnread($data);
		return $instance;
	}

	public function isEnabled() : bool {
		return $this->enabled;
	}

	public function getType() : string {
		return $this->type;
	}

	public function showOnReplace() : bool {
		return $this->showOnReplace;
	}

	public function showCountdown() : bool {
		return $this->showCountdown;
	}

	public function showOnRestore() : bool {
		return $this->showOnRestore;
	}

	public function getCountdownStart() : int {
		return $this->countdownStart;
	}

	public function getNotificationRadius() : int {
		return $this->notificationRadius;
	}

	public function getMessages() : array {
		return $this->messages;
	}

	public function getMessage(string $key) : string {
		return $this->messages[$key] ?? '';
	}
}