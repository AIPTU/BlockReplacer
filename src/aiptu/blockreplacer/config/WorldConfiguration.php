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

namespace aiptu\blockreplacer\config;

class WorldConfiguration {
	public function __construct(
		private bool $enabled_world_blacklist,
		private array $blacklisted_worlds,
		private bool $enabled_world_whitelist,
		private array $whitelisted_worlds,
	) {}

	/**
	 * @param array<int|string, mixed> $data
	 */
	public static function fromData(array $data) : self {
		$instance = new self(
			ConfigurationHelper::readBool($data, 'enabled-world-blacklist'),
			ConfigurationHelper::readMap($data, 'blacklisted-worlds'),
			ConfigurationHelper::readBool($data, 'enabled-world-whitelist'),
			ConfigurationHelper::readMap($data, 'whitelisted-worlds'),
		);
		ConfigurationHelper::checkForUnread($data);
		return $instance;
	}

	public function isWorldBlacklistEnabled() : bool {
		return $this->enabled_world_blacklist;
	}

	public function getBlacklistedWorlds() : array {
		return $this->blacklisted_worlds;
	}

	public function isWorldWhitelistEnabled() : bool {
		return $this->enabled_world_whitelist;
	}

	public function getWhitelistedWorlds() : array {
		return $this->whitelisted_worlds;
	}
}