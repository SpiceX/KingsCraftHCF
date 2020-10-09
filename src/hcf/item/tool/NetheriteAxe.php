<?php


namespace hcf\item\tool;

use pocketmine\item\Axe;
use pocketmine\item\TieredTool;
use hcf\item\ItemIds;


class NetheriteAxe extends Axe implements INetheriteTool
{

	public function __construct(int $meta = 0, int $count = 1)
	{
		parent::__construct(ItemIds::NETHERITE_AXE, $meta, 'Netherite Axe', TieredTool::TIER_DIAMOND);
	}

	public function getMaxDurability(): int
	{
		return 407;
	}
}