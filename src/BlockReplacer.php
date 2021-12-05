<?php

declare(strict_types=1);

namespace aiptu\blockreplacer;

use JackMD\UpdateNotifier\UpdateNotifier;
use pocketmine\block\BlockFactory;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\item\LegacyStringToItemParserException;
use pocketmine\item\StringToItemParser;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\World;
use function class_exists;
use function count;
use function explode;
use function gettype;
use function in_array;
use function rename;
use function strval;

final class BlockReplacer extends PluginBase implements Listener
{
	private const MODE_BLACKLIST = 0;
	private const MODE_WHITELIST = 1;

	private int $mode;

	public function onEnable(): void
	{
		if (!class_exists(UpdateNotifier::class)) {
			$this->getLogger()->error('UpdateNotifier virion not found. Download BlockReplacer at https://poggit.pmmp.io/p/BlockReplacer for a pre-compiled phar');
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}

		$this->loadConfig();

		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		UpdateNotifier::checkUpdate($this->getDescription()->getName(), $this->getDescription()->getVersion());
	}

	public function onBlockBreak(BlockBreakEvent $event): void
	{
		if (((bool) $this->getConfig()->get('ignore-cancelled', true)) && $event->isCancelled()) {
			return;
		}

		$block = $event->getBlock();
		$player = $event->getPlayer();
		$world = $block->getPosition()->getWorld();

		if (!$block->getBreakInfo()->isToolCompatible($event->getItem())) {
			return;
		}

		$defaultReplace = StringToItemParser::getInstance()->parse(strval($this->getConfig()->get('default-replace', 'minecraft:bedrock'))) ?? LegacyStringToItemParser::getInstance()->parse(strval($this->getConfig()->get('default-replace', 'minecraft:bedrock')));
		$blockReplace = null;
		$customReplace = null;

		foreach ((array) $this->getConfig()->getNested('blocks.list', []) as $value) {
			$explode = explode('=', strval($value));

			if (count($explode) === 1) {
				$blockReplace = StringToItemParser::getInstance()->parse(strval($value)) ?? LegacyStringToItemParser::getInstance()->parse(strval($value));
			} elseif (count($explode) === 2) {
				$blockReplace = StringToItemParser::getInstance()->parse($explode[0]) ?? LegacyStringToItemParser::getInstance()->parse($explode[0]);
				$customReplace = StringToItemParser::getInstance()->parse($explode[1]) ?? LegacyStringToItemParser::getInstance()->parse($explode[1]);
			}

			if ($blockReplace === null) {
				return;
			}

			if ($block->getId() === $blockReplace->getId() && $block->getMeta() === $blockReplace->getMeta()) {
				if (!$this->checkWorlds($world)) {
					return;
				}

				if (!$player->hasPermission('defaultReplacer.bypass')) {
					return;
				}

				foreach ($event->getDrops() as $drops) {
					if ((bool) $this->getConfig()->get('auto-pickup', true)) {
						(!$player->getInventory()->canAddItem($drops)) ? ($world->dropItem($block->getPosition(), $drops)) : ($player->getInventory()->addItem($drops));
						(!$player->getXpManager()->canPickupXp()) ? ($world->dropExperience($block->getPosition(), $event->getXpDropAmount())) : ($player->getXpManager()->addXp($event->getXpDropAmount()));

						continue;
					}

					$world->dropItem($block->getPosition(), $drops);
					$world->dropExperience($block->getPosition(), $event->getXpDropAmount());
				}

				$event->cancel();

				$world->setBlock($block->getPosition(), BlockFactory::getInstance()->get($defaultReplace->getId(), $defaultReplace->getMeta()));

				if ($customReplace === null) {
					$world->setBlock($block->getPosition(), BlockFactory::getInstance()->get($defaultReplace->getId(), $defaultReplace->getMeta()));
				} else {
					$world->setBlock($block->getPosition(), BlockFactory::getInstance()->get($customReplace->getId(), $customReplace->getMeta()));
				}

				$this->getScheduler()->scheduleDelayedTask(new ClosureTask(
					function () use ($block, $world): void {
						$world->setBlock($block->getPosition(), BlockFactory::getInstance()->get($block->getId(), $block->getMeta()));
					}
				), 20 * $this->getConfig()->get('cooldown', 60));
			}
		}
	}

	private function loadConfig(): void
	{
		$this->saveDefaultConfig();

		if ($this->getConfig()->get('config-version', 3) !== 3) {
			$this->getLogger()->notice('Your configuration file is outdated, updating the config.yml...');
			$this->getLogger()->notice('The old configuration file can be found at config.old.yml');

			rename($this->getDataFolder() . 'config.yml', $this->getDataFolder() . 'config.old.yml');

			$this->reloadConfig();
		}

		foreach ([
			'cooldown' => 'integer',
			'auto-pickup' => 'boolean',
			'blocks.list' => 'array',
			'blocks.default-replace' => 'string',
			'worlds.list' => 'array',
		] as $option => $expectedType) {
			if (($type = gettype($this->getConfig()->getNested($option))) !== $expectedType) {
				throw new \TypeError("Config error: Option ({$option}) must be of type {$expectedType}, {$type} was given");
			}
		}

		match ($this->getConfig()->getNested('worlds.mode', 'blacklist')) {
			'blacklist' => $this->mode = self::MODE_BLACKLIST,
			'whitelist' => $this->mode = self::MODE_WHITELIST,
			default => throw new \InvalidArgumentException('Invalid mode selected, must be either "blacklist" or "whitelist"!'),
		};

		foreach ((array) $this->getConfig()->getNested('blocks.list', []) as $value) {
			$explode = explode('=', strval($value));

			try {
				if (count($explode) === 1) {
					$blockReplace = StringToItemParser::getInstance()->parse(strval($value)) ?? LegacyStringToItemParser::getInstance()->parse(strval($value));
				} elseif (count($explode) === 2) {
					$blockReplace = StringToItemParser::getInstance()->parse($explode[0]) ?? LegacyStringToItemParser::getInstance()->parse($explode[0]);
					$customReplace = StringToItemParser::getInstance()->parse($explode[1]) ?? LegacyStringToItemParser::getInstance()->parse($explode[1]);
				}
			} catch (LegacyStringToItemParserException $e) {
				throw $e;
			}
		}

		try {
			$defaultReplace = StringToItemParser::getInstance()->parse(strval($this->getConfig()->get('default-replace', 'minecraft:bedrock'))) ?? LegacyStringToItemParser::getInstance()->parse(strval($this->getConfig()->get('default-replace', 'minecraft:bedrock')));
		} catch (\InvalidArgumentException $e) {
			throw $e;
		}
	}

	private function checkWorlds(World $world): bool
	{
		if ($this->mode === self::MODE_BLACKLIST) {
			return !(in_array($world->getFolderName(), (array) $this->getConfig()->getNested('worlds.list', []), true));
		}

		return in_array($world->getFolderName(), (array) $this->getConfig()->getNested('worlds.list', []), true);
	}
}
