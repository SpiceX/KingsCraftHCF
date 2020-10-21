<?php

namespace hcf\item\types;

use hcf\crate\Crate;
use hcf\item\CustomItem;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\TextFormat;

class CrateKey extends CustomItem {

    public const CRATE = "Crate";

    /**
     * CrateKey constructor.
     *
     * @param Crate $crate
     */
    public function __construct(Crate $crate) {
        $customName = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . $crate->getCustomName() . " Key";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Tap the {$crate->getCustomName()} Crate to receive rewards.";
        $this->setNamedTagEntry(new CompoundTag(self::CUSTOM));
        /** @var CompoundTag $tag */
        $tag = $this->getNamedTagEntry(self::CUSTOM);
        $tag->setString(self::CRATE, $crate->getCustomName());
        parent::__construct(self::TRIPWIRE_HOOK, $customName, $lore);
    }

    /**
     * @return int
     */
    public function getMaxStackSize(): int {
        return 64;
    }
}