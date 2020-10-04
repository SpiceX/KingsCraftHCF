<?php

namespace hcf\level\inventory;

use pocketmine\Player;

class EnchantInventory extends \pocketmine\inventory\EnchantInventory {

    /** @var null */
    public $random = null;

    /** @var int */
    public $bookshelfAmount = 0;

    /** @var null */
    public $levels = null;

    /** @var null */
    public $entries = null;

    /**
     * @param Player $who
     */
    public function onClose(Player $who): void {
        foreach($this->getContents() as $item) {
            if($who->getInventory()->canAddItem($item)) {
                $who->getInventory()->addItem($item);
                continue;
            }
            $directionVector = $who->getDirectionVector();
            $this->holder->getLevel()->dropItem($this->holder->subtract($directionVector->getX(), $directionVector->getFloorY(), $directionVector->getZ()), $item);
        }
        return;
    }
}