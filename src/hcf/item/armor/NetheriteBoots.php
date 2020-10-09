<?php


namespace hcf\item\armor;

use hcf\item\ItemIds;
use pocketmine\item\Armor;

class NetheriteBoots extends Armor implements INetheriteArmor
{


    public function __construct(int $meta = 0)
    {
        parent::__construct(ItemIds::NETHERITE_BOOTS, $meta, 'Netherite Boots');
    }

    /**
     * @return int
     */
    public function getDefensePoints(): int
    {
        return 3;
    }

    /**
     * @return int
     */
    public function getMaxDurability(): int
    {
        return 481;
    }
}