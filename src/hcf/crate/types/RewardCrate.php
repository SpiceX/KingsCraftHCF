<?php

namespace hcf\crate\types;

use hcf\crate\Crate;
use hcf\HCFPlayer;
use pocketmine\level\Position;
use pocketmine\utils\TextFormat;

class RewardCrate extends Crate {

    /**
     * RewardCrate constructor.
     *
     * @param Position $position
     */
    public function __construct(Position $position) {
        parent::__construct(self::REWARD, $position, []);
    }

    /**
     * @param HCFPlayer $player
     */
    public function spawnTo(HCFPlayer $player): void {
        $particle = $player->getFloatingText($this->getCustomName());
        if($particle !== null) {
            return;
        }
        $player->addFloatingText(Position::fromObject($this->getPosition()->add(0.5, 1.5, 0.5), $this->getPosition()->getLevel()), $this->getCustomName(), TextFormat::AQUA . TextFormat::BOLD . "Reward Crate\n" . TextFormat::RESET . TextFormat::WHITE . "Left click to view rewards\nRight Click to open crate");
    }

    /**
     * @param HCFPlayer $player
     */
    public function despawnTo(HCFPlayer $player): void {
        $particle = $player->getFloatingText($this->getCustomName());
        if($particle !== null) {
            $particle->despawn($player);
        }
    }
}
