<?php

namespace hcf\item;

use hcf\HCFPlayer;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\utils\TextFormat;

class AntiTrapper extends Item
{
    public function __construct()
    {
        parent::__construct(ItemIds::BONE, 0, "Bone");
        $this->getNamedTag()->setString("SpecialFeature", "AntiTrapper");
        $customName = TextFormat::RESET . TextFormat::AQUA . TextFormat::BOLD . "Anti Trapper";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Hit a player 3 times in a row with the bone and they won’t be able to place,";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "break, or open any blocks for a total time of 15 seconds.";
        $this->setCustomName($customName);
        $this->setLore($lore);
    }

    public function onAttackEntity(Entity $victim): bool
    {
        if (($victim instanceof HCFPlayer) && $this->getNamedTag()->hasTag("SpecialFeature")) {
            $victim->antiTrapperCount++;
            if ($victim->antiTrapperCount >= 3) {
                $victim->sendTitle("§cTrapped");
                $victim->sendMessage("§c> Trapped, §7You will not be able to place, break or open blocks for 15 seconds.");
                $victim->antiTrapperCooldown = strtotime('15 seconds');
                $victim->antiTrapperCount = 0;
            }
        }
        return true;
    }
}