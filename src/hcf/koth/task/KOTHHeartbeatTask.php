<?php

namespace hcf\koth\task;

use hcf\koth\KOTHManager;
use hcf\translation\TranslationException;
use pocketmine\scheduler\Task;

class KOTHHeartbeatTask extends Task {

    /** @var KOTHManager */
    private $manager;

    /**
     * KOTHHeartbeatTask constructor.
     *
     * @param KOTHManager $manager
     */
    public function __construct(KOTHManager $manager) {
        $this->manager = $manager;
    }

    /**
     * @param int $currentTick
     *
     * @throws TranslationException
     */
    public function onRun(int $currentTick) {
        if($this->manager->getGame() !== null) {
            $this->manager->getGame()->tick();
        }
    }
}
