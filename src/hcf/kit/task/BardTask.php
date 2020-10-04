<?php

namespace hcf\kit\task;

use hcf\HCF;
use hcf\HCFPlayer;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\item\Item;
use pocketmine\scheduler\Task;

class BardTask extends Task
{

    /** @var HCFPlayer */
    private $player;

    /**
     * ResetEffectsTask constructor.
     *
     * @param HCFPlayer $player
     */
    public function __construct(HCFPlayer $player)
    {
        $this->player = $player;
    }

    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick): void
    {
        if ($this->player->isClosed() || $this->player->getClass() !== HCFPlayer::BARD) {
            $this->player->getCore()->getScheduler()->cancelTask($this->getTaskId());
            $this->player->getBossBar()->update(HCF::SERVER_NAME, 1);
            return;
        }
        if ($this->player->getBardEnergy() !== 100) {
            $energy = $this->player->getBardEnergy() + 5;
            $this->player->setBardEnergy($energy);
            $this->player->playXpLevelUpSound();
        }
        $item = $this->player->getInventory()->getItemInHand();
        switch ($item->getId()) {
            case Item::SUGAR:
                if ($this->player->getFaction() !== null) {
                    $onlineMembers = $this->player->getFaction()->getOnlineMembers();
                    foreach ($onlineMembers as $member) {
                        if ($member->distance($this->player) > 20) {
                            if (!$member->hasEffect(Effect::SPEED)) {
                                if ($member->getClass() !== null && $member->getClass() !== HCFPlayer::MINER) {
                                    $this->player->getCore()->getScheduler()->scheduleTask(new ResetEffectsTask($member));
                                }
                            }
                            continue;
                        }
                        $member->addEffect(new EffectInstance(Effect::getEffect(Effect::SPEED), 100, 1));
                    }
                }
                return;
            case Item::BLAZE_POWDER:
                if ($this->player->getFaction() !== null) {
                    $onlineMembers = $this->player->getFaction()->getOnlineMembers();
                    foreach ($onlineMembers as $member) {
                        if ($member->distance($this->player) > 20) {
                            if (!$member->hasEffect(Effect::STRENGTH)) {
                                if ($member->getClass() !== null) {
                                    $this->player->getCore()->getScheduler()->scheduleTask(new ResetEffectsTask($member));
                                }
                            }
                            continue;
                        }
                        $member->addEffect(new EffectInstance(Effect::getEffect(Effect::STRENGTH), 100, 0));
                    }
                }
                return;
                break;
            case Item::IRON_INGOT:
                if ($this->player->getFaction() !== null) {
                    $onlineMembers = $this->player->getFaction()->getOnlineMembers();
                    foreach ($onlineMembers as $member) {
                        if ($member->distance($this->player) > 20) {
                            if (!$member->hasEffect(Effect::RESISTANCE)) {
                                if ($member->getClass() !== null && $member->getClass() !== HCFPlayer::MINER) {
                                    $this->player->getCore()->getScheduler()->scheduleTask(new ResetEffectsTask($member));
                                }
                            }
                            continue;
                        }
                        $member->addEffect(new EffectInstance(Effect::getEffect(Effect::RESISTANCE), 100, 0));
                    }
                }
                return;
            case Item::FEATHER:
                if ($this->player->getFaction() !== null) {
                    $onlineMembers = $this->player->getFaction()->getOnlineMembers();
                    foreach ($onlineMembers as $member) {
                        if ($member->distance($this->player) > 20) {
                            continue;
                        }
                        $member->addEffect(new EffectInstance(Effect::getEffect(Effect::JUMP), 100, 0));
                    }
                }
                return;
            case Item::GHAST_TEAR:
                if ($this->player->getFaction() !== null) {
                    $onlineMembers = $this->player->getFaction()->getOnlineMembers();
                    foreach ($onlineMembers as $member) {
                        if ($member->distance($this->player) > 20) {
                            continue;
                        }
                        $member->addEffect(new EffectInstance(Effect::getEffect(Effect::REGENERATION), 100, 0));
                    }
                }
                return;
            default:
        }
    }
}