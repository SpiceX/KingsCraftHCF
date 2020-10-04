<?php

namespace hcf\item;

use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\TextFormat;

class AntiTrapper extends CustomItem
{

    public const HIT_COUNT = "HitCount";
    public const LAST_HIT_TIME = "LastHitTime";
    public const FIRST_HIT_TIME = "FirstHitTime";
    public const USES = "AntiTrapperUses";

    /** @var CompoundTag */
    private $customCompound;
    /** @var CompoundTag */
    private $namedTagEntry;

    /**
     * AntiTrapper constructor.
     */
    public function __construct()
    {
        parent::__construct(ItemIds::BONE, "Bone");
        $this->customCompound = new CompoundTag(self::CUSTOM);
        $this->getNamedTag()->setString("SpecialFeature", "AntiTrapper");
        $customName = TextFormat::RESET . TextFormat::AQUA . TextFormat::BOLD . "Anti Trapper";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Hit a player 3 times in a row with the bone and he won’t be able to place,";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "break, or open any blocks for a total time of 15 seconds.";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::AQUA . "AntiTrapper Uses: " . TextFormat::WHITE . 10;
        $this->setNamedTagEntry($this->customCompound);
        /** @var CompoundTag $tag */
        $this->namedTagEntry = $this->getNamedTagEntry(self::CUSTOM);
        if ($this->namedTagEntry instanceof CompoundTag) {
            $this->namedTagEntry->setInt(self::HIT_COUNT, 0);
            $this->namedTagEntry->setInt(self::LAST_HIT_TIME, 0);
            $this->namedTagEntry->setInt(self::FIRST_HIT_TIME, 0);
            $this->namedTagEntry->setInt(self::USES, 10);
        }
        $this->setCustomName($customName);
        $this->setLore($lore);
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