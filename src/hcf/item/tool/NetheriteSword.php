<?php


namespace hcf\item\tool;

use hcf\item\ItemIds;
use pocketmine\item\Sword;
use pocketmine\item\TieredTool;

class NetheriteSword extends Sword implements INetheriteTool
{

    public function __construct(int $meta = 0)
    {
        parent::__construct(ItemIds::NETHERITE_SWORD, $meta, 'Netherite Sword', TieredTool::TIER_DIAMOND);
    }

    public function getMaxDurability(): int
    {
        return 407;
    }
}