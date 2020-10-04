<?php

namespace hcf\item\types;

use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;

class SplashPotion extends \pocketmine\item\SplashPotion {

    /**
     * @param Player $player
     * @param Vector3 $directionVector
     *
     * @return bool
     */
    public function onClickAir(Player $player, Vector3 $directionVector): bool {
        $nbt = Entity::createBaseNBT($player->add(0, $player->getEyeHeight(), 0), $directionVector, $player->yaw, $player->pitch);
        $this->addExtraTags($nbt);
        $projectile = Entity::createEntity($this->getProjectileEntityType(), $player->getLevel(), $nbt, $player);
        if($projectile !== null) {
            $projectile->setMotion($projectile->getMotion()->multiply($this->getThrowForce()));
        }
        $this->count--;
        if($projectile instanceof Projectile) {
            $projectileEv = new ProjectileLaunchEvent($projectile);
            $projectileEv->call();
            if($projectileEv->isCancelled()) {
                $projectile->flagForDespawn();
            }
            else {
                $projectile->spawnToAll();
                $player->getLevel()->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_THROW, 0, Entity::PLAYER);
            }
        }
        elseif($projectile !== null) {
            $projectile->spawnToAll();
        }
        else {
            return false;
        }
        return true;
    }

    /**
     * @param Player $player
     * @param Block $blockReplace
     * @param Block $blockClicked
     * @param int $face
     * @param Vector3 $clickVector
     *
     * @return bool
     */
    public function onActivate(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector): bool {
        $nbt = Entity::createBaseNBT($player->add(0, $player->getEyeHeight(), 0), $player->getDirectionVector(), $player->yaw, $player->pitch);
        $this->addExtraTags($nbt);
        $projectile = Entity::createEntity($this->getProjectileEntityType(), $player->getLevel(), $nbt, $player);
        if($projectile !== null) {
            $projectile->setMotion($projectile->getMotion()->multiply($this->getThrowForce()));
        }
        $this->count--;
        if($projectile instanceof Projectile) {
            $projectileEv = new ProjectileLaunchEvent($projectile);
            $projectileEv->call();
            if($projectileEv->isCancelled()) {
                $projectile->flagForDespawn();
            }
            else {
                $projectile->spawnToAll();
                $player->getLevel()->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_THROW, 0, Entity::PLAYER);
            }
        }
        elseif($projectile !== null) {
            $projectile->spawnToAll();
        }
        else {
            return false;
        }
        return true;
    }
}