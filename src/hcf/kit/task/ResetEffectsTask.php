<?php

namespace hcf\kit\task;

use hcf\HCFPlayer;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\scheduler\Task;

class ResetEffectsTask extends Task {

    /** @var HCFPlayer */
    private $player;

    /**
     * ResetEffectsTask constructor.
     *
     * @param HCFPlayer $player
     */
    public function __construct(HCFPlayer $player) {
        $this->player = $player;
    }

    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick) {
        if($this->player->isClosed() or $this->player->isOnline() === false) {
            return;
        }
        $this->player->removeAllEffects();
        switch($this->player->getClass()) {
            case HCFPlayer::ARCHER:
                $this->player->addEffect(new EffectInstance(Effect::getEffect(Effect::SPEED), 999999999, 2));
                $this->player->addEffect(new EffectInstance(Effect::getEffect(Effect::RESISTANCE), 999999999, 0));
                return;
                break;
            case HCFPlayer::BARD:
                $this->player->addEffect(new EffectInstance(Effect::getEffect(Effect::SPEED), 999999999, 1));
                $this->player->addEffect(new EffectInstance(Effect::getEffect(Effect::REGENERATION), 999999999, 0));
                $this->player->addEffect(new EffectInstance(Effect::getEffect(Effect::RESISTANCE), 999999999, 0));
                return;
                break;
            case HCFPlayer::MINER:
                $this->player->addEffect(new EffectInstance(Effect::getEffect(Effect::HASTE), 999999999, 1));
                $this->player->addEffect(new EffectInstance(Effect::getEffect(Effect::FIRE_RESISTANCE), 999999999, 0));
                $this->player->addEffect(new EffectInstance(Effect::getEffect(Effect::NIGHT_VISION), 999999999, 0));
                return;
                break;
            case HCFPlayer::ROGUE:
                $this->player->addEffect(new EffectInstance(Effect::getEffect(Effect::SPEED), 999999999, 2));
                $this->player->addEffect(new EffectInstance(Effect::getEffect(Effect::JUMP), 999999999, 1));
                $this->player->addEffect(new EffectInstance(Effect::getEffect(Effect::RESISTANCE), 999999999, 0));
                return;
                break;
            default:
                return;
                break;
        }
    }
}