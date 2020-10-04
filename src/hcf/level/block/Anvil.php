<?php

namespace hcf\level\block;

use hcf\level\inventory\AnvilInventory;
use hcf\network\WindowIds;
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
        if($player instanceof Player) {
            $player->addWindow(new AnvilInventory($this), WindowIds::ANVIL);
        }
        return true;
    }
}