<?php

namespace hcf\item;

use hcf\HCFPlayer;
use pocketmine\block\Block;
use pocketmine\block\BlockToolType;
use pocketmine\entity\Entity;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\item\TieredTool;
use pocketmine\utils\TextFormat;

class LumberAxe extends TieredTool
{

    public function __construct()
    {
        parent::__construct(Item::IRON_AXE, 0, "Iron Axe", self::TIER_IRON);
        $this->getNamedTag()->setString("SpecialFeature", "LumberAxe");
        $customName = TextFormat::RESET . TextFormat::AQUA . TextFormat::BOLD . "Lumber Axe";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Hit a player 3 times in a row with the axe,";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "and it'll break the enemy's helmet.";
        $this->setCustomName($customName);
        $this->setLore($lore);
    }

    public function getBlockToolType(): int
    {
        return BlockToolType::TYPE_AXE;
    }

    public function getBlockToolHarvestLevel(): int
    {
        return $this->tier;
    }

    public function getAttackPoints(): int
    {
        return self::getBaseDamageFromTier($this->tier) - 1;
    }

    public function onDestroyBlock(Block $block): bool
    {
        if ($block->getHardness() > 0) {
            return $this->applyDamage(2.5);
        }
        return false;
    }

    public function onAttackEntity(Entity $victim): bool
    {
        if (($victim instanceof HCFPlayer) && $this->getName() === "Iron Axe" && $this->hasEnchantment(Enchantment::BANE_OF_ARTHROPODS)) {
            $armorInventory = $victim->getArmorInventory();
            $victim->lumberAxeCount++;
            if ($victim->lumberAxeCount >= 3) {
                $armorInventory->setHelmet(Item::get(Item::AIR));
                $victim->lumberAxeCount = 0;
            }
        }
        return $this->applyDamage(2);
    }
}