<?php

namespace hcf\level\block;

use hcf\HCFPlayer;
use hcf\level\form\AnvilForm;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Durable;
use pocketmine\item\Item;
use pocketmine\Player;

class Anvil extends \pocketmine\block\Anvil {



    /**
     * @param Item $item
     * @param Player|null $player
     *
     * @return bool
     */
    public function onActivate(Item $item, Player $player = null): bool {
        if($player instanceof HCFPlayer) {
            $player->sendForm(new AnvilForm());
            //$player->addWindow(new AnvilInventory($this), WindowIds::ANVIL);
        }

        return true;
    }
    
    public static function repairInventory(Player $player){
        $inventory = [];
        $count = 0;
        foreach ($player->getInventory()->getContents() as $item) {
            if ($item instanceof Durable) {
                $count++;
                $player->getInventory()->removeItem($item);
                $item->setDamage(0);
                $inventory[] = $item;
            }
        }
        foreach ($inventory as $item) {
            if ($player->getInventory()->canAddItem($item)) {
                $player->getInventory()->addItem($item);
            } else {
                $player->getLevel()->dropItem($player->asVector3(), $item);
            }
        }
        $player->sendMessage("§aAll your items in your inventory were repaired §e($count).");
    }
}