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

class AutoPickupConfiguration {
	public function __construct(
		private bool $enabled_auto_pickup,
	) {}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function fromData(array $data) : self {
		$instance = new self(
			ConfigurationHelper::readBool($data, 'enabled'),
		);
		ConfigurationHelper::checkForUnread($data);
		return $instance;
	}

	public function isAutoPickupEnabled() : bool {
		return $this->enabled_auto_pickup;
	}
}