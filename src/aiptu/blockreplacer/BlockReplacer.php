<?php
declare(strict_types=1);

namespace aiptu\blockreplacer;

use JackMD\ConfigUpdater\ConfigUpdater;
use JackMD\UpdateNotifier\UpdateNotifier;
use pocketmine\block\BlockFactory;
use pocketmine\block\Solid;
use pocketmine\item\ItemFactory;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;

class BlockReplacer extends PluginBase implements Listener {

    public function onEnable() : void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->checkConfig();
        UpdateNotifier::checkUpdate($this->getDescription()->getName(), $this->getDescription()->getVersion());
    }

    private function checkConfig() : void {
        $this->saveDefaultConfig();
        ConfigUpdater::checkUpdate($this, $this->getConfig(), "config-version", 1);
        foreach ([
            "cooldown" => "integer",
            "auto-pickup" => "boolean",
            "blocks" => "array",
            "blocks-replace" => "string",
            "worlds" => "array"
        ] as $option => $expectedType) {
            if (($type = gettype($this->getConfig()->getNested($option))) != $expectedType) {
                throw new \TypeError("Config error: Option ($option) must be of type $expectedType, $type was given");
            }
        }
        if (!isset($this->getConfig()->getAll()["blocks"])) return;
        foreach ($this->getConfig()->getAll()["blocks"] as $item) {
            $explode = explode("=", $item);
            if (count($explode) === 1) {
                $b = ItemFactory::fromString((string) $item);
            } elseif (count($explode) === 2) {
                $b = ItemFactory::fromString((string) $explode[0]);
                $b = ItemFactory::fromString((string) $explode[1]);
            }
        }
        if (empty(($r = $this->getConfig()->get("blocks-replace", "minecraft:bedrock")))) return;
        $i = ItemFactory::fromString((string) $r);
    }

    /**
    * @param BlockBreakEvent $event
    *
    * @return void
    */
    public function onBlockBreak(BlockBreakEvent $event) : void {
        if ($event->isCancelled()) return;
        $block = $event->getBlock();
        $player = $event->getPlayer();
        if (!isset($this->getConfig()->getAll()["blocks"])) return;
        if (empty($this->getConfig()->get("blocks-replace", "minecraft:bedrock"))) return;
        if(!$block->isCompatibleWithTool($event->getItem())) return;
        $blockReplace = ItemFactory::fromString((string) $this->getConfig()->get("blocks-replace", "minecraft:bedrock"));
        $replaceBlock = null;
        $customReplace = null;
        foreach ($this->getConfig()->getAll()["blocks"] as $value) {
            $explode = explode("=", $value);
            if (count($explode) === 1) {
                $replaceBlock = ItemFactory::fromString((string) $value);
            } elseif (count($explode) === 2) {
                $replaceBlock = ItemFactory::fromString((string) $explode[0]);
                $customReplace = ItemFactory::fromString((string) $explode[1]);
            }
            if ($block->getId() === $replaceBlock->getId() && $block->getDamage() === $replaceBlock->getDamage()) {
                if (!$player->hasPermission("blockreplacer.bypass")) return;
                if (!$block instanceof Solid) return;
                if (in_array($block->getLevelNonNull()->getFolderName(), $this->getConfig()->getAll()["worlds"])) return;
                foreach ($event->getDrops() as $drops) {
                    if ((bool) $this->getConfig()->get("auto-pickup", true)) {
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
                    function(int $currentTick) use ($block) : void {
                        $block->getLevelNonNull()->setBlock($block->asVector3(), BlockFactory::get($block->getId(), $block->getDamage()));
                    }
                ), 20 * $this->getConfig()->get("cooldown", 60) ?? 60);
            }
        }
    }
}