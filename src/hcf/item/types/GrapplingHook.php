<?php

namespace hcf\item\types;

use hcf\item\CustomItem;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\TextFormat;

class GrapplingHook extends CustomItem {

    public const USES = "Uses";

    /**
     * GrapplingHook constructor.
     *
     * @param int $uses
     */
    public function __construct(int $uses = 25) {
        $customName = TextFormat::RESET . TextFormat::AQUA . TextFormat::BOLD . "Grappling Hook";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GREEN . "Uses left: $uses";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Travel to a place faster.";
        $this->setNamedTagEntry(new CompoundTag(self::CUSTOM));
        /** @var CompoundTag $tag */
        $tag = $this->getNamedTagEntry(self::CUSTOM);
        $tag->setInt(self::USES, $uses);
        parent::__construct(self::FISHING_ROD, $customName, $lore);
    }

    /**
     * @return int
     */
    public function getMaxStackSize(): int {
        return 1;
    }

    /**
     * @return int
     */
    public function getCooldownTicks(): int {
        return 20;
    }
}