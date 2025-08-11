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

namespace aiptu\blockreplacer\utils;

use pocketmine\block\Block;
use pocketmine\block\Crops;
use pocketmine\block\Flowable;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\enchantment\AvailableEnchantmentRegistry;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;
use pocketmine\utils\TextFormat;
use function array_map;
use function explode;
use function is_array;
use function str_contains;

class ItemParser {
	/**
	 * Parses items from an array using item names.
	 *
	 * @param array $items the array of item data
	 *
	 * @return array<Item> the parsed items
	 */
	public static function parse(array $items) : array {
		$outputItems = [];

		foreach ($items as $itemData) {
			if (!is_array($itemData) || !isset($itemData['item'])) {
				continue;
			}

			$item = self::parseItem($itemData);
			$outputItems[] = $item;
		}

		return $outputItems;
	}

	/**
	 * Parses an individual item from item data array.
	 *
	 * @param array $itemData the item data
	 *
	 * @return Item the parsed item, or a default Item object if parsing fails
	 */
	public static function parseItem(array $itemData) : Item {
		$item = VanillaItems::AIR();

		if (isset($itemData['item'])) {
			$itemName = (string) $itemData['item'];
			$parsedItem = self::parseItemFromString($itemName);
			if ($parsedItem !== null) {
				$item = $parsedItem;
			}
		}

		// Set other properties if present
		self::setOptionalProperty($itemData, 'amount', fn ($value) => $item->setCount(Utils::parseAmount($value)));

		self::setOptionalProperty($itemData, 'name', fn ($value) => $item->setCustomName(TextFormat::colorize($value)));

		self::setOptionalProperty($itemData, 'lore', fn ($value) => $item->setLore(array_map(static fn (string $value) : string => TextFormat::colorize($value), $value)));

		self::setOptionalProperty($itemData, 'enchantments', fn ($value) => self::parseEnchantments($item, $value));

		return $item;
	}

	/**
	 * Parses an item from a string.
	 *
	 * @param string $itemString the string representation of the item
	 *
	 * @return Item|null the parsed item, or null if parsing fails
	 */
	public static function parseItemFromString(string $itemString) : ?Item {
		return StringToItemParser::getInstance()->parse($itemString);
	}

	/**
	 * Parses a block from a string.
	 *
	 * @param string $blockString the string representation of the block
	 *
	 * @return Block the parsed block, or a default Block object if parsing fails
	 */
	public static function parseBlock(string $blockString) : Block {
		$parsedItem = self::parseItemFromString($blockString);
		if ($parsedItem === null) {
			return VanillaBlocks::AIR(); // Return a default Block object if parsing fails
		}

		$parsedBlock = $parsedItem->getBlock();

		// Check if the block string has the format "string:id"
		if (str_contains($blockString, ':')) {
			[$blockName, $blockId] = explode(':', $blockString);

			// Check if the parsed block is either an instance of Crops or Flowable
			if ($parsedBlock instanceof Crops || $parsedBlock instanceof Flowable) {
				$newAge = (int) $blockId;
				/** @phpstan-ignore-next-line */
				$parsedBlock->setAge($newAge);
			}
		}

		return $parsedBlock;
	}

	/**
	 * Parses enchantments and adds them to the item.
	 *
	 * @param Item  $item         the item to add enchantments to
	 * @param array $enchantments the array of enchantment data
	 */
	public static function parseEnchantments(Item $item, array $enchantments) : void {
		$enchantmentParser = StringToEnchantmentParser::getInstance();

		foreach ($enchantments as $enchantmentData) {
			if (!is_array($enchantmentData) || !isset($enchantmentData['name'], $enchantmentData['level'])) {
				continue;
			}

			$enchantmentName = (string) $enchantmentData['name'];
			$enchantmentLevel = (int) $enchantmentData['level'];

			$enchantment = $enchantmentParser->parse($enchantmentName);

			if ($enchantment !== null && self::isEnchantmentValid($enchantment, $item, $enchantmentLevel)) {
				$item->addEnchantment(new EnchantmentInstance($enchantment, $enchantmentLevel));
			}
		}
	}

	/**
	 * Checks if an enchantment is valid for the given item.
	 *
	 * @param Enchantment $enchantment the enchantment to validate
	 * @param Item        $item        the item to check compatibility against
	 * @param int         $level       the enchantment level to validate
	 *
	 * @return bool true if the enchantment is valid, false otherwise
	 */
	public static function isEnchantmentValid(Enchantment $enchantment, Item $item, int $level) : bool {
		return AvailableEnchantmentRegistry::getInstance()->isAvailableForItem($enchantment, $item) && self::isEnchantmentLevelValid($enchantment, $level);
	}

	/**
	 * Checks if the enchantment level is within the valid range.
	 *
	 * @param Enchantment $enchantment the enchantment
	 * @param int         $level       the enchantment level
	 *
	 * @return bool true if the enchantment level is valid, false otherwise
	 */
	private static function isEnchantmentLevelValid(Enchantment $enchantment, int $level) : bool {
		$maxLevel = $enchantment->getMaxLevel();
		if ($level > $maxLevel) {
			return false;
		}

		return !($level < 1);
	}

	/**
	 * Sets an optional property on the item based on its presence in the item data.
	 *
	 * @param array    $itemData     the item data
	 * @param string   $propertyName the name of the property
	 * @param callable $setterFn     the function to set the property
	 */
	private static function setOptionalProperty(array $itemData, string $propertyName, callable $setterFn) : void {
		if (isset($itemData[$propertyName])) {
			$setterFn($itemData[$propertyName]);
		}
	}
}