<?php

namespace hcf\item\tool;

use hcf\item\ItemIds;
use pocketmine\item\Shovel;
use pocketmine\item\TieredTool;

class NetheriteShovel extends Shovel implements INetheriteTool
{

    public function __construct(int $meta = 0)
    {
        parent::__construct(ItemIds::NETHERITE_SHOVEL, $meta, 'Netherite Shovel', TieredTool::TIER_DIAMOND);
    }

    public function getMaxDurability(): int
    {
        return 407;
    }
}