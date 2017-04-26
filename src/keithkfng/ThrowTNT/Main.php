<?php

namespace keithkfng\ThrowTNT;

use pocketmine\entity\Entity;
use pocketmine\entity\PrimedTNT;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\PluginTask;

class Main extends PluginBase implements Listener{
    
	public $tntCooldown = [ ];
	public $tntCooldownTime = [ ];

    public function onEnable(){
        $this->getLogger()->info("ThrowTNT enabled");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new CooldownTask($this), 20);
    }

    public function onPlayerInteract(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        if($player->getInventory()->getItemInHand()->getId() === Item::BLAZE_ROD){
            if(!isset($this->tntCooldown[$player->getName()])){
                $nbt = new CompoundTag("", [
                    "Pos" => new ListTag("Pos", [
                        new DoubleTag("", $player->x),
                        new DoubleTag("", $player->y + $player->getEyeHeight()),
                        new DoubleTag("", $player->z)
                    ]),
                    "Motion" => new ListTag("Motion", [
                        new DoubleTag("", -sin($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI)),
                        new DoubleTag("", -sin($player->pitch / 180 * M_PI)),
                        new DoubleTag("", cos($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI))
                    ]),
                    "Rotation" => new ListTag("Rotation", [
                        new FloatTag("", $player->yaw),
                        new FloatTag("", $player->pitch)
                    ]),
                ]);
                $tnt = Entity::createEntity("PrimedTNT", $player->getLevel(), $nbt, null);
                $tnt->setMotion($tnt->getMotion()->multiply(2));
                $tnt->spawnTo($player);
                $this->tntCooldown[$player->getName()] = $player->getName();
                $time = "60";
                $this->tntCooldownTime[$player->getName()] = $time;
            }else{
                $player->sendMessage("§cYou can't use your gun for another ".$this->tntCooldownTime[$player->getName()]." seconds.");
            }
        }
    }
    
    public function onEntityExplode(EntityExplodeEvent $event){
        $entity = $event->getEntity();
        if($entity instanceof PrimedTNT){
            $event->setCancelled();
        }
    }

    public function onDisable(){
        $this->getLogger()->info("ThrowTNT disabled");
    }

}

class CooldownTask extends PluginTask{

    public function __construct($plugin){
        $this->plugin = $plugin;
        parent::__construct($plugin);
    }
  
    public function onRun($tick){
        foreach($this->plugin->tntCooldown as $player){
	    if($this->plugin->tntCooldownTime[$player] <= 0){
	        unset($this->plugin->tntCooldown[$player]);
	        unset($this->plugin->tntCooldownTime[$player]);
	    }else{
	        $this->plugin->tntCooldownTime[$player]--;
	    }
        }
    }

}
