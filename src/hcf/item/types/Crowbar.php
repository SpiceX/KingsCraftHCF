<?php

namespace hcf\item\types;

use hcf\item\CustomItem;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\TextFormat;

class Crowbar extends CustomItem {

    public const SPAWNER_USES = "SpawnerUses";
    public const END_PORTAL_FRAME_USES = "EndPortalFrameUses";

    /**
     * Crowbar constructor.
     *
     * @param int $spawnerUses
     * @param int $endPortalFrameUses
     */
    public function __construct(int $spawnerUses, int $endPortalFrameUses) {
        $customName = TextFormat::RESET . TextFormat::DARK_AQUA . TextFormat::BOLD . "Crowbar";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::AQUA . "Spawner Uses: " . TextFormat::WHITE . $spawnerUses;
        $lore[] = TextFormat::RESET . TextFormat::AQUA . "End Portal Frame Uses: " . TextFormat::WHITE . $endPortalFrameUses;
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Tap a spawner or an end portal frame to break and obtain it.";
        $this->setNamedTagEntry(new CompoundTag(self::CUSTOM));
        /** @var CompoundTag $tag */
        $tag = $this->getNamedTagEntry(self::CUSTOM);
        $tag->setInt(self::SPAWNER_USES, $spawnerUses);
        $tag->setInt(self::END_PORTAL_FRAME_USES, $endPortalFrameUses);
        parent::__construct(self::DIAMOND_HOE, $customName, $lore);
    }
}