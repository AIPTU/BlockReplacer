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

use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\item\LegacyStringToItemParserException;
use pocketmine\item\StringToItemParser;
use pocketmine\Server;
use pocketmine\world\Position;
use function explode;
use function implode;
use function str_replace;
use function strtolower;
use function trim;

final class Utils
{
	public static function posToStr(Position $position): string
	{
		return implode(':', [$position->getFloorX(), $position->getFloorY(), $position->getFloorZ(), $position->getWorld()->getFolderName()]);
	}

	public static function strToPos(string $position): Position
	{
		[$x, $y, $z, $world] = explode(':', $position);
		return new Position((int) $x, (int) $y, (int) $z, Server::getInstance()->getWorldManager()->getWorldByName($world));
	}

	public static function parseItem(string $input): ?Item
	{
		$string = strtolower(str_replace([' ', 'minecraft:'], ['_', ''], trim($input)));
		try {
			$item = StringToItemParser::getInstance()->parse($string) ?? LegacyStringToItemParser::getInstance()->parse($string);
		} catch (LegacyStringToItemParserException $e) {
			return null;
		}

		return $item->isNull() ? null : $item;
	}

	public static function parseBlock(string $input): ?Block
	{
		$string = strtolower(str_replace([' ', 'minecraft:'], ['_', ''], trim($input)));
		try {
			$item = StringToItemParser::getInstance()->parse($string) ?? LegacyStringToItemParser::getInstance()->parse($string);
		} catch (LegacyStringToItemParserException $e) {
			return null;
		}
		$block = $item->getBlock();

		return $block->canBePlaced() || ($block instanceof Air) ? $block : null;
	}
}
