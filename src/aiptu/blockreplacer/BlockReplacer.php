<?php

/*
 * Copyright (c) 2021-2022 AIPTU
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/AIPTU/BlockReplacer
 */

declare(strict_types=1);

namespace aiptu\blockreplacer;

use aiptu\blockreplacer\config\BadConfigurationException;
use aiptu\blockreplacer\config\Configuration;
use aiptu\blockreplacer\config\PermissionConfiguration;
use aiptu\blockreplacer\data\BlockData;
use aiptu\blockreplacer\task\BlockTask;
use aiptu\blockreplacer\utils\Utils;
use DaPigGuy\PiggyCustomEnchants\CustomEnchantManager;
use DaPigGuy\PiggyCustomEnchants\PiggyCustomEnchants;
use DiamondStrider1\Sounds\SoundFactory;
use JackMD\UpdateNotifier\UpdateNotifier;
use pocketmine\block\Block;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\EventPriority;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\item\Item;
use pocketmine\nbt\JsonNbtParser;
use pocketmine\nbt\NbtDataException;
use pocketmine\nbt\NbtException;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use function array_map;
use function array_values;
use function class_exists;
use function explode;
use function in_array;
use function mt_rand;
use function str_ends_with;

final class BlockReplacer extends PluginBase
{
	use SingletonTrait;

	/** @var array<BlockData> */
	private array $blocks = [];

	private Configuration $configuration;

	public function onLoad(): void
	{
		self::setInstance($this);

		try {
			$this->configuration = Configuration::fromData($this->getConfig()->getAll());
		} catch (BadConfigurationException $e) {
			$this->getLogger()->alert('Failed to load the configuration: ' . $e->getMessage());
			$this->getLogger()->alert('Please fix the errors and restart the server.');
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}

		if (str_ends_with($this->getDescription()->getVersion(), '-dev')) {
			$this->getLogger()->warning('You are using the Development version of BlockReplacer. The plugin will experience errors, crashes, or bugs. Only use this version if you are testing. Do not use the Development version in production!');
		}
	}

	public function onEnable(): void
	{
		if (!$this->validateVirions()) {
			return;
		}
		UpdateNotifier::checkUpdate($this->getDescription()->getName(), $this->getDescription()->getVersion());

		$this->getScheduler()->scheduleRepeatingTask(new BlockTask(), 20);

		$this->getServer()->getPluginManager()->registerEvent(
			BlockBreakEvent::class,
			function (BlockBreakEvent $event): void {
				$block = $event->getBlock();
				$player = $event->getPlayer();
				$position = $block->getPosition();
				$world = $position->getWorld();
				$default_replace = $this->getConfiguration()->getBlock()->getDefaultReplace();
				$default_time = $this->getConfiguration()->getBlock()->getDefaultTime();

				foreach ($this->getConfiguration()->getBlock()->getListBlocks() as $k => $v) {
					$data = array_map(static function (string $value): string {
						return $value;
					}, explode('=', $k));
					[$previousBlock, $nextBlock, $time] = [$this->getBlock($data[0]), $this->getBlock($data[1] ?? $default_replace), (int) ($data[2] ?? $default_time)];

					if ($block->isSameState($previousBlock)) {
						if ($player->hasPermission(PermissionConfiguration::NAME)) {
							if (!$this->checkWorld($player->getWorld())) {
								return;
							}

							if (isset($v['drops'])) {
								foreach ($v['drops'] as $drops) {
									$item = $this->getItem($drops['item']);
									$tags = null;
									if (isset($drops['nbt'])) {
										try {
											$tags = JsonNbtParser::parseJson($drops['nbt']);
										} catch (NbtDataException $e) {
											$this->getLogger()->error($e->getMessage());
										}
									}
									if ($tags !== null) {
										try {
											$item->setNamedTag($tags);
										} catch (NbtException $e) {
											$this->getLogger()->error($e->getMessage());
										}
									}
									if (isset($drops['amount'])) {
										$item->setCount($drops['amount']);
									}
									if (isset($drops['name'])) {
										$item->setCustomName(TextFormat::colorize($drops['name']));
									}
									if (isset($drops['lore'])) {
										$item->setLore(array_map(static fn (string $value): string => TextFormat::colorize($value), $drops['lore']));
									}
									if (isset($drops['enchantments'])) {
										foreach ($drops['enchantments'] as $enchantmentData) {
											if (!isset($enchantmentData['name']) || !isset($enchantmentData['level'])) {
												$this->getLogger()->error('Invalid enchantment configuration');
												continue;
											}
											$enchantment = StringToEnchantmentParser::getInstance()->parse($enchantmentData['name']) ?? ((($plugin = $this->getServer()->getPluginManager()->getPlugin('PiggyCustomEnchants')) instanceof PiggyCustomEnchants && $plugin->isEnabled()) ? CustomEnchantManager::getEnchantmentByName($enchantmentData['name']) : null);
											if ($enchantment !== null) {
												$item->addEnchantment(new EnchantmentInstance($enchantment, $enchantmentData['level']));
											}
										}
									}
									$chance = $drops['chance'] ?? 100;
									if (mt_rand(1, 100) <= $chance) {
										$event->setDrops([$item]);
									}
								}
							}

							foreach ($event->getDrops() as $drops) {
								if ($this->getConfiguration()->getAutoPickup()->isAutoPickupEnabled()) {
									(!$player->getInventory()->canAddItem($drops)) ? ($world->dropItem($position->add(0.5, 0.5, 0.5), $drops)) : ($player->getInventory()->addItem($drops));
									(!$player->getXpManager()->canPickupXp()) ? ($world->dropExperience($position->add(0.5, 0.5, 0.5), $event->getXpDropAmount())) : ($player->getXpManager()->addXp($event->getXpDropAmount()));

									continue;
								}

								$world->dropItem($position->add(0.5, 0.5, 0.5), $drops);
								$world->dropExperience($position->add(0.5, 0.5, 0.5), $event->getXpDropAmount());
							}

							$event->cancel();

							if (!isset($this->blocks[Utils::posToStr($position)])) {
								$this->blocks[Utils::posToStr($position)] = new BlockData($position, $previousBlock, $nextBlock, $time);
							}
							$this->blocks[Utils::posToStr($position)]->break($player);
						}
					}
				}
			},
			EventPriority::HIGH,
			$this,
			true,
		);
	}

	public function onDisable(): void
	{
		foreach ($this->getBlocks() as $block) {
			$block->refill();
		}
	}

	/**
	 * @return array<BlockData>
	 */
	public function getBlocks(): array
	{
		return array_values($this->blocks);
	}

	public function removeBlock(BlockData $block): void
	{
		unset($this->blocks[Utils::posToStr($block->getPosition())]);
	}

	public function getItem(string $string): Item
	{
		$item = Utils::parseItem($string);
		if ($item === null) {
			throw new BadConfigurationException('Unable to parse "' . $string . '" to a valid item');
		}

		return $item;
	}

	public function getBlock(string $string): Block
	{
		$block = Utils::parseBlock($string);
		if ($block === null) {
			throw new BadConfigurationException('Unable to parse "' . $string . '" to a valid block');
		}

		return $block;
	}

	public function checkWorld(World $world): bool
	{
		$blacklist = $this->getConfiguration()->getWorld()->isWorldBlacklistEnabled();
		$whitelist = $this->getConfiguration()->getWorld()->isWorldWhitelistEnabled();
		$world_name = $world->getFolderName();

		if ($blacklist === $whitelist) {
			return true;
		}

		if ($blacklist) {
			$disallowed_worlds = $this->getConfiguration()->getWorld()->getBlacklistedWorlds();
			return !in_array($world_name, $disallowed_worlds, true);
		}

		if ($whitelist) {
			$allowed_worlds = $this->getConfiguration()->getWorld()->getWhitelistedWorlds();
			return in_array($world_name, $allowed_worlds, true);
		}

		return false;
	}

	public function getConfiguration(): Configuration
	{
		return $this->configuration;
	}

	/**
	 * Checks if the required virions/libraries are present before enabling the plugin.
	 */
	private function validateVirions(): bool
	{
		$requiredVirions = [
			'Sounds' => SoundFactory::class,
			'UpdateNotifier' => UpdateNotifier::class,
		];

		$return = true;

		foreach ($requiredVirions as $name => $class) {
			if (!class_exists($class)) {
				$this->getLogger()->error($name . ' virion was not found. Download BlockReplacer at https://poggit.pmmp.io/p/BlockReplacer.');
				$this->getServer()->getPluginManager()->disablePlugin($this);
				$return = false;
				break;
			}
		}

		return $return;
	}
}
