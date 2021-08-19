<?php
declare(strict_types=1);

namespace aiptu\blockreplacer;

use JackMD\ConfigUpdater\ConfigUpdater;
use pocketmine\block\BlockFactory;
use pocketmine\block\Solid;
use pocketmine\item\ItemFactory;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;

class BlockReplacer extends PluginBase implements Listener{
    
    public function onLoad() : void{
        $this->checkConfig();
    }
    
    public function onEnable() : void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }
    
    private function checkConfig() : void{
        $this->saveDefaultConfig();
        ConfigUpdater::checkUpdate($this, $this->getConfig(), "config-version", 1);
        if(!is_array($this->getConfig()->getAll()["blocks"])){
            $this->getLogger()->error("Config error: blocks must an array, disable plugin...");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }
        if(!is_array($this->getConfig()->getAll()["worlds"])){
            $this->getLogger()->error("Config error: worlds must an array, disable plugin...");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }
        if(!is_bool($this->getConfig()->get("auto-pickup", true))){
            $this->getLogger()->error("Config error: auto-pickup must an bool, disable plugin...");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }
        if(!is_numeric($this->getConfig()->get("cooldown", 60))){
            $this->getLogger()->error("Config error: cooldown must an integer, disable plugin...");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }
        foreach($this->getConfig()->getAll()["blocks"] as $item){
            try{
                if(!isset($this->getConfig()->getAll()["blocks"])) return;
                $explode = explode("×", $item);
                if(count($explode) === 1){
                    $b = ItemFactory::fromString((string)$item);
                }elseif(count($explode) === 2){
                    $b = ItemFactory::fromString((string) $explode[0]);
                    $b = ItemFactory::fromString((string) $explode[1]);
                }
            }catch(\InvalidArgumentException $e){
                $this->getLogger()->error("Could not parse " . $item . " as a valid item, disable plugin...");
                $this->getServer()->getPluginManager()->disablePlugin($this);
                return;
            }
        }
        try{
            if(empty($this->getConfig()->get("blocks-replace", "minecraft:bedrock"))) return;
            $i = ItemFactory::fromString((string)$this->getConfig()->get("blocks-replace", "minecraft:bedrock"));
        }catch(\InvalidArgumentException $e){
            $this->getLogger()->error("Could not parse " . $i . " as a valid item, disable plugin...");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }
    }
    
    /**
    * @param BlockBreakEvent $event
    * @return void
    */
    public function onBlockBreak(BlockBreakEvent $event) : void{
        $block = $event->getBlock();
        $player = $event->getPlayer();
        if($event->isCancelled()) return;
        if(!$event->getBlock()->isCompatibleWithTool($event->getItem())) return;
        if(!isset($this->getConfig()->getAll()["blocks"])) return;
        if(empty($this->getConfig()->get("blocks-replace", "minecraft:bedrock"))) return;
        $blockReplace = ItemFactory::fromString((string)$this->getConfig()->get("blocks-replace", "minecraft:bedrock"));
        $replaceBlock = null;
        $customReplace  = null;
        foreach($this->getConfig()->getAll()["blocks"] as $value){
            $explode = explode("×", $value);
            if(count($explode) === 1){
                $replaceBlock = ItemFactory::fromString((string) $value);
            }elseif(count($explode) === 2){
                $replaceBlock = ItemFactory::fromString((string) $explode[0]);
                $customReplace  = ItemFactory::fromString((string) $explode[1]);
            }
            if($block->getId() === $replaceBlock->getId() and $block->getDamage() === $replaceBlock->getDamage()){
                if(!$player->hasPermission("blockreplacer.bypass")) return;
                if(!$block instanceof Solid) return;
                if(in_array($block->getLevelNonNull()->getFolderName(), $this->getConfig()->getAll()["worlds"])) return;
                foreach ($event->getDrops() as $drops){
                    if((bool)$this->getConfig()->get("auto-pickup", true)){
                        if(!$player->getInventory()->canAddItem($drops)) return;
                        if(!$player->canPickupXp()) return;
                        $player->getInventory()->addItem($drops);
                        $player->addXp($event->getXpDropAmount());
                        continue;
                    }
                    $block->getLevelNonNull()->dropItem($block->asVector3(), $drops);
                    $block->getLevelNonNull()->dropExperience($block->asVector3(), $event->getXpDropAmount());
                }
                $event->setCancelled();
                $block->getLevelNonNull()->setBlock($block->asVector3(), BlockFactory::get($blockReplace->getId(), $blockReplace->getDamage()));
                if(is_null($customReplace)){
                    $block->getLevelNonNull()->setBlock($block->asVector3(), BlockFactory::get($blockReplace->getId(), $blockReplace->getDamage()));
                }else{
                    $block->getLevelNonNull()->setBlock($block->asVector3(), BlockFactory::get($customReplace ->getId(), $customReplace ->getDamage()));
                }
                $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
                    function(int $currentTick) use ($block) : void{
                        $block->getLevelNonNull()->setBlock($block->asVector3(), BlockFactory::get($block->getId(), $block->getDamage()));
                    }
                ), 20 * $this->getConfig()->get("cooldown", 60) ?? 60);
            }
        }
    }
}
