<?php

declare(strict_types=1);

namespace aiptu\blockreplacer;

use JackMD\UpdateNotifier\UpdateNotifier;
use pocketmine\block\BlockFactory;
use pocketmine\block\Solid;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\item\ItemFactory;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
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
                if (!$block instanceof Solid) {
                    return;
                }

                if (!$this->checkWorlds($block)) {
                    return;
                }

                if (!$player->hasPermission('blockreplacer.bypass')) {
                    return;
                }

                foreach ($event->getDrops() as $drops) {
                    if ((bool) $this->getConfig()->get('auto-pickup', true)) {
                        (!$player->getInventory()->canAddItem($drops)) ? ($block->getLevelNonNull()->dropItem($block->asVector3(), $drops)) : ($player->getInventory()->addItem($drops));
                        (!$player->canPickupXp()) ? ($block->getLevelNonNull()->dropExperience($block->asVector3(), $event->getXpDropAmount())) : ($player->addXp($event->getXpDropAmount()));

                        continue;
                    }

                    $block->getLevelNonNull()->dropItem($block->asVector3(), $drops);
                    $block->getLevelNonNull()->dropExperience($block->asVector3(), $event->getXpDropAmount());
                }

                $event->setCancelled();

                $block->getLevelNonNull()->setBlock($block->asVector3(), BlockFactory::get($blockReplace->getId(), $blockReplace->getDamage()));

                if ($customReplace === null) {
                    $block->getLevelNonNull()->setBlock($block->asVector3(), BlockFactory::get($blockReplace->getId(), $blockReplace->getDamage()));
                } else {
                    $block->getLevelNonNull()->setBlock($block->asVector3(), BlockFactory::get($customReplace->getId(), $customReplace->getDamage()));
                }

                $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
                    function (int $currentTick) use ($block): void {
                        $block->getLevelNonNull()->setBlock($block->asVector3(), BlockFactory::get($block->getId(), $block->getDamage()));
                    }
                ), 20 * $this->getConfig()->get('cooldown', 60));
            }
        }
    }

    private function checkConfig(): void
    {
        $this->saveDefaultConfig();

        if ($this->getConfig()->get('config-version', 2) !== 2) {
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

    private function checkWorlds(Solid $block): bool
    {
        $world = $block->getLevelNonNull()->getFolderName();

        if ($this->mode === self::MODE_BLACKLIST) {
            return !(in_array($world, $this->getConfig()->getAll()['worlds'], true));
        }

        return (in_array($world, $this->getConfig()->getAll()['worlds'], true));
    }
}
