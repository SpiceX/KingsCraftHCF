<?php

namespace hcf\enchant\types\boots;

use hcf\enchant\CustomEnchant;
use hcf\enchant\ToggleableEnchantment;
use hcf\enchant\traits\TickingTrait;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\Player;

class SpeedEnchant extends ToggleableEnchantment
{
    use TickingTrait;

    /** @var string */
    public $name = "Speed";
    /** @var int */
    public $maxLevel = 1;

    /** @var int */
    public $usageType = CustomEnchant::TYPE_BOOTS;
    /** @var int */
    public $itemType = CustomEnchant::ITEM_TYPE_BOOTS;


    public function tick(Player $player, Item $item, Inventory $inventory, int $slot, int $level): void
    {
        $player->addEffect(new EffectInstance(Effect::getEffect(Effect::SPEED), 40, $this->getMaxLevel()));
    }

    public function toggle(Player $player, Item $item, Inventory $inventory, int $slot, int $level, bool $toggle): void
    {
        if (!$toggle) {
            $player->addEffect(new EffectInstance(Effect::getEffect(Effect::SPEED), 40, $this->getMaxLevel()));
        }
    }
}