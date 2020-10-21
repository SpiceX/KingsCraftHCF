<?php

namespace hcf\task;

use hcf\HCF;
use hcf\HCFPlayer;
use pocketmine\Player;
use pocketmine\scheduler\Task;

class SpecialItemCooldown extends Task
{
    /** @var Player */
    private $player;
    /** @var string */
    private $specialItem;
    /** @var int */
    private $seconds = 20;

    /**
     * SpecialItemCooldown constructor.
     * @param HCFPlayer $player
     * @param string $specialItem
     */
    public function __construct(HCFPlayer $player, string $specialItem)
    {
        $this->player = $player;
        $this->specialItem = $specialItem;
    }

    public function onRun(int $currentTick): void
    {
        if (!$this->player->isOnline()) {
            $this->cancelTask();
        }
        if ($this->seconds <= 0) {
            switch ($this->specialItem) {
                case 'NetherStar':
                    $this->player->hasStarCooldown = false;
                    break;
                case 'AntiTrapper':
                    $this->player->hasAntiTrapperCooldown = false;
                    break;
                case 'Fireworks':
                    $this->player->hasFireworksCooldown = false;
                    break;
                case 'InvisibilitySak':
                    $this->player->hasInvisibilitySakCooldown = false;
                    break;
                case 'TeleportationBall':
                    $this->player->hasTeleportationBallCooldown = false;
                    break;
                case 'LumberAxe':
                    $this->player->hasLumberAxeCooldown = false;
                    break;
            }
            $this->seconds = 20;
            $this->cancelTask();
        } else {
            switch ($this->specialItem) {
                case 'NetherStar':
                    $this->player->hasStarCooldown = true;
                    $this->player->sendPopup("§eNetherStar Cooldown: " . $this->seconds);
                    break;
                case 'AntiTrapper':
                    $this->player->hasAntiTrapperCooldown = true;
                    $this->player->sendPopup("§eAntiTrapper Cooldown: " . $this->seconds);
                    break;
                case 'Fireworks':
                    $this->player->hasFireworksCooldown = true;
                    $this->player->sendPopup("§eFireworks Cooldown: " . $this->seconds);
                    break;
                case 'InvisibilitySak':
                    $this->player->hasInvisibilitySakCooldown = true;
                    $this->player->sendPopup("§eInvisibilitySak Cooldown: " . $this->seconds);
                    break;
                case 'TeleportationBall':
                    $this->player->hasTeleportationBallCooldown = true;
                    $this->player->sendPopup("§eTeleportationBall Cooldown: " . $this->seconds);
                    break;
                case 'LumberAxe':
                    $this->player->hasLumberAxeCooldown = true;
                    $this->player->sendPopup("§eLumber Axe Cooldown: " . $this->seconds);
                    break;
            }
        }
        $this->seconds--;
    }

    private function cancelTask(): void
    {
        HCF::getInstance()->getScheduler()->cancelTask($this->getTaskId());
    }
}