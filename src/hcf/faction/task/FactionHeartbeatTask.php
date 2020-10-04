<?php

namespace hcf\faction\task;

use hcf\faction\FactionManager;
use pocketmine\scheduler\Task;

class FactionHeartbeatTask extends Task {

    /** @var FactionManager */
    private $manager;

    /**
     * FactionHeartbeatTask constructor.
     *
     * @param FactionManager $manager
     */
    public function __construct(FactionManager $manager) {
        $this->manager = $manager;
    }

    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick) {
        foreach($this->manager->getFactions() as $faction) {
            $faction->tick();
        }
    }
}
