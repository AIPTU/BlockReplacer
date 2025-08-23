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

use aiptu\blockreplacer\utils\ItemParser;
use aiptu\blockreplacer\utils\Utils;
use pocketmine\item\Item;

class DropRule {
	public function __construct(
		private array $itemData,
	) {}

	public function shouldDrop() : bool {
		$chance = $this->itemData['chance'] ?? 100;
		return Utils::checkChance($chance);
	}

	public function generateDrop() : ?Item {
		if (!$this->shouldDrop()) {
			return null;
		}

		return ItemParser::parseItem($this->itemData);
	}

	public static function fromArray(array $data) : ?self {
		if (!isset($data['item'])) {
			return null;
		}

		return new self($data);
	}
}