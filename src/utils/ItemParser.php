<?php

/*
 * Copyright (c) 2021-2023 AIPTU
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/AIPTU/BlockReplacer
 */

declare(strict_types=1);

namespace aiptu\blockreplacer\utils;

use DaPigGuy\PiggyCustomEnchants\CustomEnchantManager;
use DaPigGuy\PiggyCustomEnchants\enchants\CustomEnchant;
use DaPigGuy\PiggyCustomEnchants\PiggyCustomEnchants;
use DaPigGuy\PiggyCustomEnchants\utils\Utils as PiggyUtils;
use pocketmine\block\Block;
use pocketmine\block\Crops;
use pocketmine\block\Flowable;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\ArmorInventory;
use pocketmine\item\Armor;
use pocketmine\item\Axe;
use pocketmine\item\Bow;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\ItemFlags;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\item\FishingRod;
use pocketmine\item\FlintSteel;
use pocketmine\item\Hoe;
use pocketmine\item\Item;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\item\Pickaxe;
use pocketmine\item\Shears;
use pocketmine\item\Shovel;
use pocketmine\item\StringToItemParser;
use pocketmine\item\Sword;
use pocketmine\item\VanillaItems;
use pocketmine\utils\TextFormat;
use function array_map;
use function class_exists;
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
		return StringToItemParser::getInstance()->parse($itemString) ?? LegacyStringToItemParser::getInstance()->parse($itemString);
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
		$piggyCustomEnchantsExists = class_exists(PiggyCustomEnchants::class);

		foreach ($enchantments as $enchantmentData) {
			if (!is_array($enchantmentData) || !isset($enchantmentData['name'], $enchantmentData['level'])) {
				continue;
			}

			$enchantmentName = (string) $enchantmentData['name'];
			$enchantmentLevel = (int) $enchantmentData['level'];

			$enchantment = null;

			if ($piggyCustomEnchantsExists) {
				$enchantment = CustomEnchantManager::getEnchantmentByName($enchantmentName);
			} else {
				$enchantment = $enchantmentParser->parse($enchantmentName);
			}

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
		if ($enchantment instanceof CustomEnchant) {
			return PiggyUtils::itemMatchesItemType($item, $enchantment->getItemType()) && self::isEnchantmentLevelValid($enchantment, $level);
		}

		$itemFlags = self::getItemFlagsByItem($item);
		if ($itemFlags === null || !($enchantment->hasPrimaryItemType($itemFlags) || $enchantment->hasSecondaryItemType($itemFlags))) {
			return false;
		}

		return self::isEnchantmentLevelValid($enchantment, $level);
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
		return $level >= 1 && ($maxLevel === -1 || $level <= $maxLevel);
	}

	/**
	 * Retrieves the item flags associated with the given item.
	 *
	 * @param Item $item the item to retrieve the item flags for
	 *
	 * @return int|null the item flags for the item, or null if the item is not supported
	 */
	private static function getItemFlagsByItem(Item $item) : ?int {
		if ($item instanceof Armor) {
			$slot = $item->getArmorSlot();
			$slotFlags = [
				ArmorInventory::SLOT_HEAD => ItemFlags::HEAD,
				ArmorInventory::SLOT_CHEST => ItemFlags::TORSO,
				ArmorInventory::SLOT_LEGS => ItemFlags::LEGS,
				ArmorInventory::SLOT_FEET => ItemFlags::FEET,
			];

			return $slotFlags[$slot] ?? null;
		}

		$itemFlags = [
			Sword::class => ItemFlags::SWORD,
			Bow::class => ItemFlags::BOW,
			Hoe::class => ItemFlags::HOE,
			Shears::class => ItemFlags::SHEARS,
			FlintSteel::class => ItemFlags::FLINT_AND_STEEL,
			Axe::class => ItemFlags::AXE,
			Pickaxe::class => ItemFlags::PICKAXE,
			Shovel::class => ItemFlags::SHOVEL,
			FishingRod::class => ItemFlags::FISHING_ROD,
		];

		foreach ($itemFlags as $class => $flags) {
			if ($item instanceof $class) {
				return $flags;
			}
		}

		return null;
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
