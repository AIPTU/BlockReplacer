<?php

declare(strict_types=1);

namespace aiptu\blockreplacer;

use JackMD\UpdateNotifier\UpdateNotifier;
use pocketmine\block\BlockFactory;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\item\LegacyStringToItemParserException;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\World;
use function class_exists;
use function count;
use function explode;
use function gettype;
use function in_array;
use function rename;

final class BlockReplacer extends PluginBase implements Listener
{
	private const MODE_BLACKLIST = 0;
	private const MODE_WHITELIST = 1;

	private int $mode;

	public function onEnable(): void
	{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		$this->checkConfig();

		if ((bool) $this->getConfig()->get('check-update', true)) {
			$this->checkUpdate();
		}
	}

	public function onBlockBreak(BlockBreakEvent $event): void
	{
		$block = $event->getBlock();
		$player = $event->getPlayer();
		$world = $block->getPosition()->getWorld();

		if (!$block->getBreakInfo()->isToolCompatible($event->getItem())) {
			return;
		}

		if (!isset($this->getConfig()->getAll()['blocks']['list'])) {
			return;
		}

		$defaultReplace = LegacyStringToItemParser::getInstance()->parse($this->getConfig()->getNested('blocks.default-replace', 'minecraft:bedrock'));
		$blockReplace = null;
		$customReplace = null;

		foreach ($this->getConfig()->getAll()['blocks']['list'] as $value) {
			$explode = explode('=', $value);

			if (count($explode) === 1) {
				$blockReplace = LegacyStringToItemParser::getInstance()->parse($value);
			} elseif (count($explode) === 2) {
				$blockReplace = LegacyStringToItemParser::getInstance()->parse($explode[0]);
				$customReplace = LegacyStringToItemParser::getInstance()->parse($explode[1]);
			}

			if ($block->getId() === $blockReplace->getId() && $block->getMeta() === $blockReplace->getMeta()) {
				if (!$this->checkWorlds($world)) {
					return;
				}

				if (!$player->hasPermission('blockreplacer.bypass')) {
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

	private function checkConfig(): void
	{
		$this->saveDefaultConfig();

		if ($this->getConfig()->get('config-version', 3) !== 3) {
			$this->getLogger()->notice('Your configuration file is outdated, updating the config.yml...');
			$this->getLogger()->notice('The old configuration file can be found at config.old.yml');

			rename($this->getDataFolder() . 'config.yml', $this->getDataFolder() . 'config.old.yml');

			$this->reloadConfig();
		}

		foreach ([
			'check-update' => 'boolean',
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

		match ($this->getConfig()->getNested('worlds.mode')) {
			'blacklist' => $this->mode = self::MODE_BLACKLIST,
			'whitelist' => $this->mode = self::MODE_WHITELIST,
			default => throw new \InvalidArgumentException('Invalid mode selected, must be either "blacklist" or "whitelist"!'),
		};

		try {
			$defaultReplace = LegacyStringToItemParser::getInstance()->parse($this->getConfig()->getNested('blocks.default-replace', 'minecraft:bedrock'));
		} catch (LegacyStringToItemParserException $e) {
			throw $e;
		}

		if (!isset($this->getConfig()->getAll()['blocks']['list'])) {
			return;
		}

		foreach ($this->getConfig()->getAll()['blocks']['list'] as $value) {
			$explode = explode('=', $value);

			try {
				if (count($explode) === 1) {
					$blockReplace = LegacyStringToItemParser::getInstance()->parse($value);
				} elseif (count($explode) === 2) {
					$blockReplace = LegacyStringToItemParser::getInstance()->parse($explode[0]);
					$customReplace = LegacyStringToItemParser::getInstance()->parse($explode[1]);
				}
			} catch (LegacyStringToItemParserException $e) {
				throw $e;
			}
		}
	}

	private function checkUpdate(): void
	{
		if (!class_exists(UpdateNotifier::class)) {
			$this->getLogger()->error('UpdateNotifier virion not found. Download BlockReplacer at https://poggit.pmmp.io/p/BlockReplacer for a pre-compiled phar');
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}

		UpdateNotifier::checkUpdate($this->getDescription()->getName(), $this->getDescription()->getVersion());
	}

	private function checkWorlds(World $world): bool
	{
		if ($this->mode === self::MODE_BLACKLIST) {
			return !(in_array($world->getFolderName(), $this->getConfig()->getAll()['worlds']['list'], true));
		}

		return in_array($world->getFolderName(), $this->getConfig()->getAll()['worlds']['list'], true);
	}
}
