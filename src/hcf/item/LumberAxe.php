<?php

namespace hcf\item;

use pocketmine\block\Block;
use pocketmine\block\BlockToolType;
use pocketmine\item\Item;
use pocketmine\item\TieredTool;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\TextFormat;

class LumberAxe extends TieredTool
{
    public const HIT_COUNT = "HitCount";

    /** @var CompoundTag */
    private $customCompound;
    /** @var CompoundTag */
    private $namedTagEntry;

    public function __construct()
    {
        parent::__construct(Item::IRON_AXE, 0, "Iron Axe", self::TIER_IRON);
        $this->customCompound = new CompoundTag(CustomItem::CUSTOM);
        $this->getNamedTag()->setString("SpecialFeature", "LumberAxe");
        $customName = TextFormat::RESET . TextFormat::AQUA . TextFormat::BOLD . "Lumber Axe";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Hit a player 3 times in a row with the axe,";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "and it'll break the enemy's helmet.";
        $this->setNamedTagEntry($this->customCompound);
        /** @var CompoundTag $tag */
        $this->namedTagEntry = $this->getNamedTagEntry(CustomItem::CUSTOM);
        if ($this->namedTagEntry instanceof CompoundTag) {
            $this->namedTagEntry->setInt(self::HIT_COUNT, 0);
        }
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

    /**
     * @return CompoundTag
     */
    public function getCustomCompound(): CompoundTag
    {
        return $this->customCompound;
    }

    /**
     * @return CompoundTag
     */
    public function getCustomNamedTagEntry(): CompoundTag
    {
        return $this->namedTagEntry;
    }
}