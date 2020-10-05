<?php

namespace hcf\item\entity;

use hcf\HCF;
use hcf\HCFPlayer;
use hcf\task\SpecialItemCooldown;
use pocketmine\entity\projectile\Snowball;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\level\particle\SnowballPoofParticle;

class TeleportationBall extends Snowball {

    /**
     * @param ProjectileHitEvent $event
     */
    protected function onHit(ProjectileHitEvent $event) : void{
        for($i = 0; $i < 6; ++$i){
            $this->level->addParticle(new SnowballPoofParticle($this));
        }
        if($event instanceof ProjectileHitEntityEvent) {
            $entityHit = $event->getEntityHit();
            $player = $this->getOwningEntity();
            if($player instanceof HCFPlayer && $entityHit instanceof HCFPlayer) {
                if ($player->hasTeleportationBallCooldown){
                    return;
                }
                if($player->distance($entityHit) <= 7) {
                    HCF::getInstance()->getScheduler()->scheduleRepeatingTask(new SpecialItemCooldown($player, 'TeleportationBall'), 20);
                    $position = $player->asPosition();
                    $player->teleport($entityHit);
                    $entityHit->teleport($position);
                }
            }
        }
    }
}