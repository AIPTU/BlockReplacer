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

namespace aiptu\blockreplacer\data;

use aiptu\blockreplacer\utils\Utils;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\Position;
use function array_values;

class BlockDataManager {
	use SingletonTrait;

	/** @var array<string, BlockData> */
	private array $blockDataMap = [];

	/**
	 * Add or overwrite a BlockData object in the map.
	 */
	public function addBlockData(BlockData $blockData) : void {
		$key = self::getBlockDataKey($blockData->getPosition());
		$this->blockDataMap[$key] = $blockData;
	}

	/**
	 * Remove a BlockData object from the map based on its reference.
	 */
	public function removeBlockData(BlockData $blockData) : void {
		$key = self::getBlockDataKey($blockData->getPosition());
		unset($this->blockDataMap[$key]);
	}

	/**
	 * Get a BlockData object from the map based on the position.
	 */
	public function getBlockData(Position $position) : ?BlockData {
		$key = self::getBlockDataKey($position);
		return $this->blockDataMap[$key] ?? null;
	}

	/**
	 * Get a list of BlockData objects stored in the map.
	 *
	 * @return array<BlockData>
	 */
	public function getBlockDataList() : array {
		return array_values($this->blockDataMap);
	}

	/**
	 * Restore all blocks to their original state.
	 */
	public function restoreAllBlocks() : void {
		foreach ($this->blockDataMap as $blockData) {
			$blockData->restoreBlock();
		}
	}

	/**
	 * Generate a unique key for the BlockData based on its position.
	 */
	private static function getBlockDataKey(Position $position) : string {
		return Utils::serializePosition($position);
	}
}