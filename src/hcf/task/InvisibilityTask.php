<?php

namespace hcf\task;

use hcf\HCF;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class InvisibilityTask extends Task
{

    /** @var Player */
    private $player;

    /** @var int */
    private $seconds = 0;

    /**
     * InvisibilityTask constructor.
     * @param Player $player
     */
    public function __construct(Player $player)
    {
        $this->player = $player;
        $this->player->addEffect(new EffectInstance(Effect::getEffect(Effect::INVISIBILITY), 300, 2, false));
        $this->player->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, true);
        $this->player->setNameTagVisible(false);
        foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
            $onlinePlayer->hidePlayer($player);
        }
    }

    public function onRun(int $currentTick): void
    {
        if ($this->seconds >= 15) {
            $this->player->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, false);
            $this->player->setNameTagVisible(true);
            foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
                $onlinePlayer->showPlayer($this->player);
            }
            HCF::getInstance()->getScheduler()->cancelTask($this->getTaskId());
        }
        $this->seconds++;
    }
}