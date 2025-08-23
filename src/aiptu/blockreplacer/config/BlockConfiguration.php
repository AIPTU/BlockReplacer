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

use aiptu\blockreplacer\data\BlockRule;
use aiptu\blockreplacer\utils\ItemParser;
use aiptu\blockreplacer\utils\Utils;
use pocketmine\block\Block;
use function array_map;
use function count;
use function explode;

class BlockConfiguration {
	/** @var array<string, BlockRule> */
	private array $blockRules = [];

	public function __construct(
		private string $default_replace,
		private int $default_time,
		private array $list_blocks,
	) {
		$this->parseBlockRules();
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function fromData(array $data) : self {
		$instance = new self(
			ConfigurationHelper::readString($data, 'default-replace', false),
			ConfigurationHelper::readInt($data, 'default-time', 1, 86400),
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

	/**
	 * Get all parsed block rules.
	 *
	 * @return array<string, BlockRule>
	 */
	public function getBlockRules() : array {
		return $this->blockRules;
	}

	/**
	 * Get a specific block rule by block state ID.
	 */
	public function getBlockRule(Block $block) : ?BlockRule {
		$blockStateId = self::getBlockStateId($block);
		return $this->blockRules[$blockStateId] ?? null;
	}

	/**
	 * Check if a block has a replacement rule.
	 */
	public function hasBlockRule(Block $block) : bool {
		$blockStateId = self::getBlockStateId($block);
		return isset($this->blockRules[$blockStateId]);
	}

	/**
	 * Parse all block rules from the configuration data.
	 */
	private function parseBlockRules() : void {
		foreach ($this->list_blocks as $data => $config) {
			$blockRule = $this->parseBlockRule($data, $config);

			$blockStateId = self::getBlockStateId($blockRule->getFromBlock());
			$this->blockRules[$blockStateId] = $blockRule;
		}
	}

	/**
	 * Parse a single block rule from configuration data.
	 */
	private function parseBlockRule(string $data, array $config) : BlockRule {
		[$fromBlock, $toBlock, $time] = $this->parseBlockData($data);

		$drops = $config['drops'] ?? [];
		$experience = $config['experience'] ?? [];

		return new BlockRule($fromBlock, $toBlock, $time, $drops, $experience);
	}

	/**
	 * Parse block data string (e.g., "cobblestone=stone=5").
	 */
	private function parseBlockData(string $data) : array {
		$dataParts = array_map('trim', explode('=', $data, 3));
		$numParts = count($dataParts);

		$fromBlock = ItemParser::parseBlock($dataParts[0]);
		$toBlock = $numParts >= 2 ? ItemParser::parseBlock($dataParts[1]) : ItemParser::parseBlock($this->default_replace);
		$time = $numParts >= 3 ? Utils::parseAmount($dataParts[2]) : $this->default_time;

		return [$fromBlock, $toBlock, $time];
	}

	/**
	 * Generate a unique identifier for a block state.
	 */
	private static function getBlockStateId(Block $block) : string {
		return $block->getTypeId() . ':' . $block->getStateId();
	}
}