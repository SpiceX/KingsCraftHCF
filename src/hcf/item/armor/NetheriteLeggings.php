<?php

namespace hcf\item\armor;

use hcf\item\ItemIds;
use pocketmine\item\Armor;

class NetheriteLeggings extends Armor implements INetheriteArmor
{


    public function __construct(int $meta = 0)
    {
        parent::__construct(ItemIds::NETHERITE_LEGGINGS, $meta, 'Netherite Leggings');
    }

    /**
     * @return int
     */
    public function getDefensePoints(): int
    {
        return 6;
    }

    /**
     * @return int
     */
    public function getMaxDurability(): int
    {
        return 555;
    }
}