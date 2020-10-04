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
    private $seconds = 0;

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
        if ($this->seconds >= 20) {
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
            }
            $this->seconds = 0;
            $this->cancelTask();
        } else {
            switch ($this->specialItem) {
                case 'NetherStar':
                    $this->player->hasStarCooldown = true;
                    break;
                case 'AntiTrapper':
                    $this->player->hasAntiTrapperCooldown = true;
                    break;
                case 'Fireworks':
                    $this->player->hasFireworksCooldown = true;
                    break;
                case 'InvisibilitySak':
                    $this->player->hasInvisibilitySakCooldown = true;
                    break;
                case 'TeleportationBall':
                    $this->player->hasTeleportationBallCooldown = true;
                    break;
            }
        }
        $this->seconds++;
    }

    private function cancelTask(): void
    {
        HCF::getInstance()->getScheduler()->cancelTask($this->getTaskId());
    }
}