<?php


namespace hcf\item\armor;

use pocketmine\item\Armor;
use hcf\item\ItemIds;

class NetheriteHelmet extends Armor implements INetheriteArmor {


    public function __construct (int $meta = 0) {
        parent::__construct (ItemIds::NETHERITE_HELMET, $meta, 'Netherite Helmet');
    }
    
    /**
     * @return int
     */
    public function getDefensePoints (): int{
        return 3;
    }
    
    /**
     * @return int
     */
    public function getMaxDurability (): int{
        return 407;
    }
}