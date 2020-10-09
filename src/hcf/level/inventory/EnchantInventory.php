<?php

namespace hcf\level\inventory;

use pocketmine\inventory\ContainerInventory;
use pocketmine\level\Position;
use pocketmine\network\mcpe\protocol\types\inventory\UIInventorySlotOffset;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\Player;

class EnchantInventory extends ContainerInventory implements FakeInventory {

    /** @var null */
    public $random;

    /** @var int */
    public $bookshelfAmount = 0;

    /** @var null */
    public $levels;

    /** @var null */
    public $entries;

    public function getNetworkType() : int{
        return WindowTypes::ENCHANTMENT;
    }

    public function getName() : string{
        return "Enchantment Table";
    }

    public function getDefaultSize() : int{
        return 2; //1 input, 1 lapis
    }

    public function getUIOffsets() : array{
        return UIInventorySlotOffset::ENCHANTING_TABLE;
    }

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
    }
}