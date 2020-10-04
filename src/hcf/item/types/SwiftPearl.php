<?php

namespace hcf\item\types;

use pocketmine\item\EnderPearl;
use pocketmine\utils\TextFormat;

class SwiftPearl extends EnderPearl {

    /**
     * SwiftPearl constructor.
     *
     * @param int $meta
     */
    public function __construct(int $meta = 1) {
        $customName = TextFormat::RESET . TextFormat::AQUA . TextFormat::BOLD . "Swift Pearl";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Have a 5s cooldown instead of 10s.";
        $this->setCustomName($customName);
        $this->setLore($lore);
        parent::__construct($meta);
    }
}