<?php


namespace hcf\item\entity;


use hcf\HCF;
use hcf\HCFPlayer;
use hcf\task\EggSpecialCooldown;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\level\Level;
use pocketmine\math\RayTraceResult;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;

class Egg extends \pocketmine\entity\projectile\Egg
{
    public function __construct(Level $level, CompoundTag $nbt, ?Entity $shootingEntity = null)
    {
        parent::__construct($level, $nbt, $shootingEntity);
    }

    /**
     * @param Entity $entityHit
     * @param RayTraceResult $hitResult
     */
    public function onHitEntity(Entity $entityHit, RayTraceResult $hitResult): void
    {
        $ev = new ProjectileHitEntityEvent($this, $hitResult, $entityHit);
        $ev->call();

        $damage = $this->getResultDamage();

        if ($this->getOwningEntity() === null) {
            $ev = new EntityDamageByEntityEvent($this, $entityHit, EntityDamageEvent::CAUSE_PROJECTILE, $damage);
        } else {
            $owningEntity = $this->getOwningEntity();
            if ($owningEntity instanceof HCFPlayer) {
                HCF::getInstance()->getScheduler()->scheduleRepeatingTask(new EggSpecialCooldown($owningEntity), 20);
            }
            $ev = new EntityDamageByChildEntityEvent($this->getOwningEntity(), $this, $entityHit, EntityDamageEvent::CAUSE_PROJECTILE, $damage);
        }

        $entityHit->attack($ev);
        if ($entityHit instanceof Player) {
            $entityHit->addEffect(new EffectInstance(Effect::getEffect(Effect::BLINDNESS), 60));
        }
        $entityHit->setMotion($this->getOwningEntity()->getDirectionVector()->multiply(-0.3)->add(0, 0.3, 0));

        $this->isCollided = true;
        $this->flagForDespawn();
    }
}