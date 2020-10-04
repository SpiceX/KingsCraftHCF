<?php

namespace hcf\faction\task;

use hcf\HCF;
use hcf\HCFPlayer;
use pocketmine\level\Position;
use pocketmine\level\sound\EndermanTeleportSound;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

class TeleportHomeTask extends Task {

    /** @var HCFPlayer */
    private $player;

    /** @var Position */
    private $position;

    /** @var int */
    private $time;

    /** @var int */
    private $maxTime;

    /**
     * TeleportHomeTask constructor.
     *
     * @param HCFPlayer $player
     * @param int $time
     */
    public function __construct(HCFPlayer $player, int $time) {
        $this->player = $player;
        $this->position = $player->asPosition();
        $this->time = $time;
        $this->maxTime = $time;
    }

    public function onRun(int $currentTick) {
        if($this->player === null or $this->player->isClosed()) {
            HCF::getInstance()->getScheduler()->cancelTask($this->getTaskId());
            return;
        }
        $home = $this->player->getFaction()->getHome();
        if($this->player->getFloorX() !== $this->position->getFloorX() or $this->player->getFloorY() !== $this->position->getFloorY() or $this->player->getFloorZ() !== $this->position->getFloorZ()) {
            $this->player->setTeleporting(false);
            $this->player->sendTitle(TextFormat::DARK_RED . "Failed to teleport", TextFormat::GRAY . "You must stand still!");
            HCF::getInstance()->getScheduler()->cancelTask($this->getTaskId());
            return;
        }
        if($this->time >= 0) {
            $this->player->sendTitle(TextFormat::DARK_GREEN . "Teleporting in", TextFormat::GRAY . "$this->time seconds" . str_repeat(".", ($this->maxTime - $this->time) % 4));
            $this->time--;
            return;
        }
        $this->player->teleport($home);
        $this->player->sendMessage(TextFormat::GREEN . "You have successfully teleport to your location.");
        $this->player->getLevel()->addSound(new EndermanTeleportSound($this->player));
        $this->player->setTeleporting(false);
        HCF::getInstance()->getScheduler()->cancelTask($this->getTaskId());
        return;
    }
}
