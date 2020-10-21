<?php


namespace hcf\enchant\traits;


use hcf\HCF;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\Player;

trait TickingTrait
{
    /** @var HCF */
    protected $plugin;

    public function canTick(): bool
    {
        return true;
    }

    public function getTickingInterval(): int
    {
        return 1;
    }

    public function onTick(Player $player, Item $item, Inventory $inventory, int $slot, int $level): void
    {
        $this->tick($player, $item, $inventory, $slot, $level);
    }

    public function tick(Player $player, Item $item, Inventory $inventory, int $slot, int $level): void
    {

    }

    public function supportsMultipleItems(): bool
    {
        return false;
    }
}