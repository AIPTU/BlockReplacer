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

class BlockConfiguration {
	public function __construct(
		private string $default_replace,
		private int $default_time,
		private array $list_blocks,
	) {}

	/**
	 * @param array<int|string, mixed> $data
	 */
	public static function fromData(array $data) : self {
		$instance = new self(
			ConfigurationHelper::readString($data, 'default-replace'),
			ConfigurationHelper::readInt($data, 'default-time', 1),
			ConfigurationHelper::readMap($data, 'list'),
		);
		ConfigurationHelper::checkForUnread($data);
		return $instance;
	}

	public function getDefaultReplace() : string {
		return $this->default_replace;
	}

	public function getDefaultTime() : int {
		return $this->default_time;
	}

	public function getListBlocks() : array {
		return $this->list_blocks;
	}
}