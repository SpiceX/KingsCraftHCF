<?php

namespace hcf\item\entity;

use pocketmine\block\Block;
use pocketmine\entity\projectile\Throwable;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\level\sound\EndermanTeleportSound;
use pocketmine\math\RayTraceResult;
use pocketmine\network\mcpe\protocol\LevelEventPacket;

class EnderPearl extends Throwable
{

    public const NETWORK_ID = self::ENDER_PEARL;

    public function onHitBlock(Block $blockHit, RayTraceResult $hitResult): void
    {
        switch ($blockHit->getId()) {
            case Block::TRAPDOOR:
            case Block::FENCE:
            case Block::STONE_WALL:
            case Block::SPRUCE_FENCE_GATE:
            case Block::JUNGLE_FENCE_GATE:
            case Block::DARK_OAK_FENCE_GATE:
            case Block::BIRCH_FENCE_GATE:
            case Block::ACACIA_FENCE_GATE:
            case Block::FENCE_GATE:
            case Block::NETHER_BRICK_FENCE:
                $this->flagForDespawn();
                return;
        }
        parent::onHitBlock($blockHit, $hitResult);
    }

    protected function onHit(ProjectileHitEvent $event): void
    {
        $owner = $this->getOwningEntity();
        if ($owner !== null) {
            $this->level->broadcastLevelEvent($owner, LevelEventPacket::EVENT_PARTICLE_ENDERMAN_TELEPORT);
            $this->level->addSound(new EndermanTeleportSound($owner));
            $owner->teleport($event->getRayTraceResult()->getHitVector());
            $this->level->addSound(new EndermanTeleportSound($owner));

            //$owner->attack(new EntityDamageEvent($owner, EntityDamageEvent::CAUSE_FALL, 5));
        }
    }
}