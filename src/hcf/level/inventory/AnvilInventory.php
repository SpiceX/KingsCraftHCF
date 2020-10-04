<?php

namespace hcf\level\inventory;

use pocketmine\Player;

class AnvilInventory extends \pocketmine\inventory\AnvilInventory {

    /**
     * @return int
     */
    public function getDefaultSize(): int{
        return 3;
    }

    /**
     * @param Player $who
     */
    public function onClose(Player $who): void{
        foreach($this->getContents() as $item){
            foreach($who->getInventory()->addItem($item) as $doesntFit){
                $who->getLevel()->dropItem($this->holder, $doesntFit);
            }
        }
    }
}
