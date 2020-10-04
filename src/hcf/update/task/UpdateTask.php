<?php

namespace hcf\update\task;

use hcf\HCF;
use hcf\HCFPlayer;
use libs\utils\UtilsException;
use pocketmine\scheduler\Task;

class UpdateTask extends Task {

    /** @var HCF */
    private $core;

    /**
     * UpdateTask constructor.
     *
     * @param HCF $core
     */
    public function __construct(HCF $core) {
        $this->core = $core;
    }

    /**
     * @param int $currentTick
     *
     * @throws UtilsException
     */
    public function onRun(int $currentTick): void {
        foreach($this->core->getServer()->getOnlinePlayers() as $player) {
            if(!$player instanceof HCFPlayer) {
                return;
            }
            $this->core->getUpdateManager()->updateScoreboard($player);
            $this->core->getUpdateManager()->updateBossbar($player);
        }
    }
}