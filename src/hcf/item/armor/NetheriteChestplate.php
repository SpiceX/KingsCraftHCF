<?php


namespace hcf\item\armor;

use pocketmine\item\Armor;
use hcf\item\ItemIds;

class NetheriteChestplate extends Armor implements INetheriteArmor {


    public function __construct (int $meta = 0) {
        parent::__construct (ItemIds::NETHERITE_CHESTPLATE, $meta, 'Netherite Chestplate');
    }
    
    /**
     * @return int
     */
    public function getDefensePoints (): int{
        return 8;
    }
    
    /**
     * @return int
     */
    public function getMaxDurability (): int{
        return 592;
    }
}