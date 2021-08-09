<?php
declare(strict_types=1);

namespace aiptu\blockreplacer;

use JackMD\ConfigUpdater\ConfigUpdater;
use pocketmine\block\Block;
use pocketmine\block\Solid;
use pocketmine\item\Item;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;

class BlockReplacer extends PluginBase implements Listener {
    
    public function onEnable() : void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        ConfigUpdater::checkUpdate($this, $this->getConfig(), "config-version", 1);
        $this->saveDefaultConfig();
        foreach ($this->getConfig()->getAll()["blocks"] as $value) {
            $explode = explode("×", $value);
            if(count($explode) === 1){
                Item::fromString((string) $value);
            } elseif(count($explode) === 2){
                Item::fromString((string) $explode[0]);
                Item::fromString((string) $explode[1]);
            }
        }
        Item::fromString((string) $this->getConfig()->get("blocks-replace", "minecraft:bedrock"));
    }
    
    /**
    * @param BlockBreakEvent $event
    * @return void
    */
    public function onBlockBreak(BlockBreakEvent $event) : void {
        $block = $event->getBlock();
        $player = $event->getPlayer();
        if ($event->isCancelled()) return;
        if (!$event->getBlock()->isCompatibleWithTool($event->getItem())) return;
        if (!isset($this->getConfig()->getAll()["blocks"])) return;
        if (empty($this->getConfig()->get("blocks-replace", "minecraft:bedrock"))) return;
        $blockReplace = Item::fromString((string) $this->getConfig()->get("blocks-replace", "minecraft:bedrock"));
        foreach ($this->getConfig()->getAll()["blocks"] as $value) {
            $explode = explode("×", $value);
            $customreplace = null;
            if(count($explode) === 1){
                $bblock = Item::fromString((string) $value);
            } elseif(count($explode) === 2) {
                $bblock = Item::fromString((string) $explode[0]);
                $customreplace = Item::fromString((string) $explode[1]);
            }
            if ($block->getId() === $bblock->getId() and $block->getDamage() === $bblock->getDamage()) {
                if (!$block instanceof Solid) return;
                if (in_array($block->getLevelNonNull()->getFolderName(), $this->getConfig()->getAll()["worlds"])) return;
                foreach ($event->getDrops() as $drops) {
                    if ((bool) $this->getConfig()->get("auto-pickup", true)) {
                        if ($player->getInventory()->canAddItem($drops)) {
                            $player->getInventory()->addItem($drops);
                        }
                        if ($player->canPickupXp()) {
                            $player->addXp($event->getXpDropAmount());
                        }
                    } else {
                        $block->getLevelNonNull()->dropItem($block->asVector3(), Item::get($drops->getId(), $drops->getDamage(), $drops->getCount()));
                        $block->getLevelNonNull()->dropExperience($block->asVector3(), $event->getXpDropAmount());
                    }
                }
                $event->setCancelled();
                if($customreplace === null){
                    $block->getLevelNonNull()->setBlock($block->asVector3(), Block::get($blockReplace->getId(), $blockReplace->getDamage()));
                } else {
                    $block->getLevelNonNull()->setBlock($block->asVector3(), Block::get($customreplace->getId(), $customreplace->getDamage()));
                }
                $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
                    function(int $currentTick) use ($block) : void {
                        $block->getLevelNonNull()->setBlock($block->asVector3(), Block::get($block->getId(), $block->getDamage()));
                    }
                ), 20 * $this->getConfig()->get("cooldown", 60));
            }
        }
    }
}
