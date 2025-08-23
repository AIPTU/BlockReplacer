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

class ExperienceRule {
	public function __construct(
		private string $amountRange,
		private string $chanceRange,
	) {}

	public function shouldGiveExperience() : bool {
		return Utils::checkChance($this->chanceRange);
	}

	public function generateExperience() : int {
		if (!$this->shouldGiveExperience()) {
			return 0;
		}

		return Utils::parseAmount($this->amountRange);
	}

	public static function fromArray(array $data) : ?self {
		if (!isset($data['amount'])) {
			return null;
		}

		$amount = $data['amount'];
		$chance = $data['chance'] ?? 100;

		return new self((string) $amount, (string) $chance);
	}
}