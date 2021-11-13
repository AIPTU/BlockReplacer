<?php

declare(strict_types=1);

namespace aiptu\blockreplacer;

use JackMD\UpdateNotifier\UpdateNotifier;
use pocketmine\block\BlockFactory;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\item\ItemFactory;
use pocketmine\level\Level;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use function class_exists;
use function count;
use function explode;
use function gettype;
use function in_array;
use function rename;

class BlockReplacer extends PluginBase implements Listener
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

        $this->checkConfig();

        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        UpdateNotifier::checkUpdate($this->getDescription()->getName(), $this->getDescription()->getVersion());
    }

    public function onBlockBreak(BlockBreakEvent $event): void
    {
        if ($event->isCancelled()) {
            return;
        }

        $block = $event->getBlock();
        $player = $event->getPlayer();
        $world = $block->getLevelNonNull();

        if (!$block->isCompatibleWithTool($event->getItem())) {
            return;
        }

        if (!isset($this->getConfig()->getAll()['blocks'])) {
            return;
        }

        $blockReplace = ItemFactory::fromString($this->getConfig()->get('blocks-replace', 'minecraft:bedrock'));
        $replaceBlock = null;
        $customReplace = null;

        foreach ($this->getConfig()->getAll()['blocks'] as $value) {
            $explode = explode('=', $value);

            if (count($explode) === 1) {
                $replaceBlock = ItemFactory::fromString($value);
            } elseif (count($explode) === 2) {
                $replaceBlock = ItemFactory::fromString($explode[0]);
                $customReplace = ItemFactory::fromString($explode[1]);
            }

            if ($block->getId() === $replaceBlock->getId() && $block->getDamage() === $replaceBlock->getDamage()) {
                if (!$this->checkWorlds($world)) {
                    return;
                }

                if (!$player->hasPermission('blockreplacer.bypass')) {
                    return;
                }

                foreach ($event->getDrops() as $drops) {
                    if ((bool) $this->getConfig()->get('auto-pickup', true)) {
                        (!$player->getInventory()->canAddItem($drops)) ? ($world->dropItem($block, $drops)) : ($player->getInventory()->addItem($drops));
                        (!$player->canPickupXp()) ? ($world->dropExperience($block, $event->getXpDropAmount())) : ($player->addXp($event->getXpDropAmount()));

                        continue;
                    }

                    $world->dropItem($block, $drops);
                    $world->dropExperience($block, $event->getXpDropAmount());
                }

                $event->setCancelled();

                $world->setBlock($block, BlockFactory::get($blockReplace->getId(), $blockReplace->getDamage()));

                if ($customReplace === null) {
                    $world->setBlock($block, BlockFactory::get($blockReplace->getId(), $blockReplace->getDamage()));
                } else {
                    $world->setBlock($block, BlockFactory::get($customReplace->getId(), $customReplace->getDamage()));
                }

                $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
                    function (int $currentTick) use ($block, $world): void {
                        $world->setBlock($block, BlockFactory::get($block->getId(), $block->getDamage()));
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
            'cooldown' => 'integer',
            'auto-pickup' => 'boolean',
            'blocks' => 'array',
            'blocks-replace' => 'string',
            'mode' => 'string',
            'worlds' => 'array',
        ] as $option => $expectedType) {
            if (($type = gettype($this->getConfig()->getNested($option))) !== $expectedType) {
                throw new \TypeError("Config error: Option ({$option}) must be of type {$expectedType}, {$type} was given");
            }
        }

        match ($this->getConfig()->get('mode')) {
            'blacklist' => $this->mode = self::MODE_BLACKLIST,
            'whitelist' => $this->mode = self::MODE_WHITELIST,
            default => throw new \InvalidArgumentException('Invalid mode selected, must be either "blacklist" or "whitelist"!'),
        };

        if (!isset($this->getConfig()->getAll()['blocks'])) {
            return;
        }

        foreach ($this->getConfig()->getAll()['blocks'] as $item) {
            $explode = explode('=', $item);

            try {
                if (count($explode) === 1) {
                    $replaceBlock = ItemFactory::fromString($item);
                } elseif (count($explode) === 2) {
                    $replaceBlock = ItemFactory::fromString($explode[0]);
                    $customReplace = ItemFactory::fromString($explode[1]);
                }
            } catch (\InvalidArgumentException $e) {
                throw $e;
            }
        }

        try {
            $blockReplace = ItemFactory::fromString($this->getConfig()->get('blocks-replace', 'minecraft:bedrock'));
        } catch (\InvalidArgumentException $e) {
            throw $e;
        }
    }

    private function checkWorlds(Level $world): bool
    {
        if ($this->mode === self::MODE_BLACKLIST) {
            return !(in_array($world->getFolderName(), $this->getConfig()->getAll()['worlds'], true));
        }

        return in_array($world->getFolderName(), $this->getConfig()->getAll()['worlds'], true);
    }
}
