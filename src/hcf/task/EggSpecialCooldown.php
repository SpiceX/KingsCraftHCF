<?php

namespace hcf\task;

use hcf\HCF;
use hcf\HCFPlayer;
use pocketmine\Player;
use pocketmine\scheduler\Task;

class EggSpecialCooldown extends Task
{
    /** @var Player */
    private $player;
    /** @var int */
    private $seconds = 10;

    /**
     * SpecialItemCooldown constructor.
     * @param HCFPlayer $player
     */
    public function __construct(HCFPlayer $player)
    {
        $this->player = $player;
    }

    public function onRun(int $currentTick): void
    {
        if (!$this->player->isOnline()) {
            $this->cancelTask();
        }
        if ($this->seconds <= 0) {
            $this->player->hasEggCooldown = false;
            $this->seconds = 10;
            $this->cancelTask();
        } else {
            $this->player->hasEggCooldown = true;
            $this->player->sendPopup("§eEggCooldown: {$this->seconds}");
        }
        $this->seconds--;
    }

    private function cancelTask(): void
    {
        HCF::getInstance()->getScheduler()->cancelTask($this->getTaskId());
    }
}