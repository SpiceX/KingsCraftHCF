<?php

namespace hcf\item\entity;

use hcf\HCFPlayer;
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
                if($player->distance($entityHit) <= 7) {
                    $position = $player->asPosition();
                    $player->teleport($entityHit);
                    $entityHit->teleport($position);
                }
            }
        }
    }
}