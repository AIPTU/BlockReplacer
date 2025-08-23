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

use pocketmine\block\Block;
use pocketmine\item\Item;
use function count;
use function is_array;

class BlockRule {
	/** @var array<DropRule> */
	private array $dropRules = [];
	private ?ExperienceRule $experienceRule = null;

	public function __construct(
		private Block $fromBlock,
		private Block $toBlock,
		private int $time,
		array $drops,
		array $experience,
	) {
		$this->parseDropRules($drops);
		$this->parseExperienceRule($experience);
	}

	public function getFromBlock() : Block {
		return $this->fromBlock;
	}

	public function getToBlock() : Block {
		return $this->toBlock;
	}

	public function getTime() : int {
		return $this->time;
	}

	/**
	 * @return array<DropRule>
	 */
	public function getDropRules() : array {
		return $this->dropRules;
	}

	public function getExperienceRule() : ?ExperienceRule {
		return $this->experienceRule;
	}

	public function hasDropRules() : bool {
		return count($this->dropRules) > 0;
	}

	public function hasExperienceRule() : bool {
		return $this->experienceRule !== null;
	}

	/**
	 * Generate all drops for this rule.
	 *
	 * @return array<Item>
	 */
	public function generateDrops() : array {
		$drops = [];

		foreach ($this->dropRules as $dropRule) {
			$drop = $dropRule->generateDrop();
			if ($drop !== null) {
				$drops[] = $drop;
			}
		}

		return $drops;
	}

	/**
	 * Generate experience for this rule.
	 */
	public function generateExperience() : int {
		if ($this->experienceRule === null) {
			return 0;
		}

		return $this->experienceRule->generateExperience();
	}

	/**
	 * Parse drop rules from configuration array.
	 */
	private function parseDropRules(array $drops) : void {
		foreach ($drops as $dropData) {
			if (!is_array($dropData)) {
				continue;
			}

			$dropRule = DropRule::fromArray($dropData);
			if ($dropRule !== null) {
				$this->dropRules[] = $dropRule;
			}
		}
	}

	/**
	 * Parse experience rule from configuration array.
	 */
	private function parseExperienceRule(array $experience) : void {
		if (count($experience) === 0) {
			return;
		}

		$this->experienceRule = ExperienceRule::fromArray($experience);
	}
}