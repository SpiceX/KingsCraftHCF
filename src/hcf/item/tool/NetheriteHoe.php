<?php


namespace hcf\item\tool;

use hcf\item\ItemIds;
use pocketmine\item\Hoe;
use pocketmine\item\TieredTool;

class NetheriteHoe extends Hoe implements INetheriteTool
{

    public function __construct(int $meta = 0)
    {
        parent::__construct(ItemIds::NETHERITE_HOE, $meta, 'Netherite Hoe', TieredTool::TIER_DIAMOND);
    }

    public function getMaxDurability(): int
    {
        return 407;
    }
}