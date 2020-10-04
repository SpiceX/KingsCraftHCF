<?php

namespace hcf\level\task;

use hcf\HCF;
use hcf\level\LevelManager;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

class GlowstoneResetTask extends Task {

    /** @var LevelManager */
    private $manager;

    /**
     * GlowstoneResetTask constructor.
     *
     * @param LevelManager $manager
     */
    public function __construct(LevelManager $manager) {
        $this->manager = $manager;
    }

    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick) {
        $this->manager->getGlowstoneMountain()->reset();
        HCF::getInstance()->getServer()->broadcastMessage(TextFormat::YELLOW . "Glowstone mountain has reset! Next reset will be in 15 minutes!");
    }
}
