<?php

namespace hcf\kit\task;

use hcf\HCFPlayer;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\item\Item;
use pocketmine\scheduler\Task;

class SetClassTask extends Task {

    /** @var HCFPlayer */
    private $player;

    /**
     * SetClassTask constructor.
     *
     * @param HCFPlayer $player
     */
    public function __construct(HCFPlayer $player) {
        $this->player = $player;
    }

    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick): void {
        if($this->player->isClosed() || $this->player->isOnline() === false) {
            return;
        }
        $armorInventory = $this->player->getArmorInventory();
        $helmet = $armorInventory->getHelmet()->getId();
        $chestplate = $armorInventory->getChestplate()->getId();
        $leggings = $armorInventory->getLeggings()->getId();
        $boots = $armorInventory->getBoots()->getId();
        if($helmet === Item::LEATHER_HELMET && $chestplate === Item::LEATHER_CHESTPLATE && $leggings === Item::LEATHER_PANTS && $boots === Item::LEATHER_BOOTS) {
            $this->player->setClass(HCFPlayer::ARCHER);
            $this->player->addEffect(new EffectInstance(Effect::getEffect(Effect::SPEED), 999999999, 2));
            $this->player->addEffect(new EffectInstance(Effect::getEffect(Effect::RESISTANCE), 999999999, 0));
            return;
        }
        if($helmet === Item::GOLD_HELMET && $chestplate === Item::GOLD_CHESTPLATE && $leggings === Item::GOLD_LEGGINGS && $boots === Item::GOLD_BOOTS) {
            $this->player->setClass(HCFPlayer::BARD);
            $this->player->setBardEnergy(0);
            $this->player->getCore()->getScheduler()->scheduleDelayedRepeatingTask(new BardTask($this->player), 100, 100);
            $this->player->addEffect(new EffectInstance(Effect::getEffect(Effect::SPEED), 999999999, 1));
            $this->player->addEffect(new EffectInstance(Effect::getEffect(Effect::REGENERATION), 999999999, 0));
            $this->player->addEffect(new EffectInstance(Effect::getEffect(Effect::RESISTANCE), 999999999, 0));
            return;
        }
        if($helmet === Item::IRON_HELMET && $chestplate === Item::IRON_CHESTPLATE && $leggings === Item::IRON_LEGGINGS && $boots === Item::IRON_BOOTS) {
            $this->player->setClass(HCFPlayer::MINER);
            $this->player->addEffect(new EffectInstance(Effect::getEffect(Effect::HASTE), 999999999, 1));
            $this->player->addEffect(new EffectInstance(Effect::getEffect(Effect::FIRE_RESISTANCE), 999999999, 0));
            $this->player->addEffect(new EffectInstance(Effect::getEffect(Effect::NIGHT_VISION), 999999999, 0));
            return;
        }
        if($helmet === Item::CHAIN_HELMET && $chestplate === Item::CHAIN_CHESTPLATE && $leggings === Item::CHAIN_LEGGINGS && $boots === Item::CHAIN_BOOTS) {
            $this->player->setClass(HCFPlayer::ROGUE);
            $this->player->addEffect(new EffectInstance(Effect::getEffect(Effect::SPEED), 999999999, 2));
            $this->player->addEffect(new EffectInstance(Effect::getEffect(Effect::JUMP), 999999999, 1));
            $this->player->addEffect(new EffectInstance(Effect::getEffect(Effect::RESISTANCE), 999999999, 0));
            return;
        }
        //$this->player->archerTag($this->player);
        //$this->player->setClass(HCFPlayer::PLAYER);
        $this->player->removeAllEffects();
    }
}