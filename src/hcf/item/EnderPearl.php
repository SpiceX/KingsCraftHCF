<?php


namespace hcf\item;


use pocketmine\entity\Entity;
use pocketmine\entity\EntityIds;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\ProjectileItem;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;

class EnderPearl extends ProjectileItem
{
    public function __construct(int $meta = 0){
        parent::__construct(self::ENDER_PEARL, $meta, "Ender Pearl");
    }

    public function onClickAir(Player $player, Vector3 $directionVector) : bool{
        $nbt = Entity::createBaseNBT($player->add(0, $player->getEyeHeight(), 0), $directionVector, $player->yaw, $player->pitch);
        $this->addExtraTags($nbt);

        $projectile = Entity::createEntity('EnderPearl', $player->getLevelNonNull(), $nbt, $player);
        if($projectile !== null){
            $projectile->setMotion($projectile->getMotion()->multiply($this->getThrowForce()));
        }

        $this->pop();

        if($projectile instanceof Projectile){
            $projectileEv = new ProjectileLaunchEvent($projectile);
            $projectileEv->call();
            if($projectileEv->isCancelled()){
                $projectile->flagForDespawn();
            }else{
                $projectile->spawnToAll();

                $player->getLevelNonNull()->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_THROW, 0, EntityIds::PLAYER);
            }
        }elseif($projectile !== null){
            $projectile->spawnToAll();
        }else{
            return false;
        }

        return true;
    }

    public function getMaxStackSize() : int{
        return 16;
    }

    public function getProjectileEntityType() : string{
        return "ThrownEnderpearl";
    }

    public function getThrowForce() : float{
        return 1.5;
    }

    public function getCooldownTicks() : int{
        return 20;
    }
}