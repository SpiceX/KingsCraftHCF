<?php


namespace hcf\item\tool;

use hcf\item\ItemIds;
use pocketmine\item\Pickaxe;
use pocketmine\item\TieredTool;

class NetheritePickaxe extends Pickaxe implements INetheriteTool
{

    public function __construct(int $meta = 0)
    {
        parent::__construct(ItemIds::NETHERITE_PICKAXE, $meta, 'Netherite Pickaxe', TieredTool::TIER_DIAMOND);
    }

    public function getMaxDurability(): int
    {
        return 2031;
    }

}