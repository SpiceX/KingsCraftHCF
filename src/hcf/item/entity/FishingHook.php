<?php


namespace hcf\item\entity;


use hcf\item\FishingRod;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\level\Level;
use pocketmine\math\RayTraceResult;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\utils\Random;

class FishingHook extends Projectile
{
    public const NETWORK_ID = self::FISHING_HOOK;
    public $height = 0.25;
    public $width = 0.25;
    protected $gravity = 0.1;

    /**
     * FishingHook constructor.
     * @param Level $level
     * @param CompoundTag $nbt
     * @param Entity|null $owner
     */
    public function __construct(Level $level, CompoundTag $nbt, ?Entity $owner = null)
    {
        parent::__construct($level, $nbt, $owner);
        if ($owner instanceof Player) {
            $this->setPosition($this->add(0, $owner->getEyeHeight() - 0.1));
            $this->setMotion($owner->getDirectionVector()->multiply(0.4));
            $this->handleHookCasting($this->motion->x, $this->motion->y, $this->motion->z, 1.5, 1.0);
        }
    }

    /**
     * @param float $x
     * @param float $y
     * @param float $z
     * @param float $f1
     * @param float $f2
     */
    public function handleHookCasting(float $x, float $y, float $z, float $f1, float $f2): void
    {
        $rand = new Random();
        $f = sqrt($x * $x + $y * $y + $z * $z);
        $x /= $f;
        $y /= $f;
        $z /= $f;
        $x += $rand->nextSignedFloat() * 0.007499999832361937 * $f2;
        $y += $rand->nextSignedFloat() * 0.007499999832361937 * $f2;
        $z += $rand->nextSignedFloat() * 0.007499999832361937 * $f2;
        $x *= $f1;
        $y *= $f1;
        $z *= $f1;
        $this->motion->x += $x;
        $this->motion->y += $y;
        $this->motion->z += $z;
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
            $ev = new EntityDamageByChildEntityEvent($this->getOwningEntity(), $this, $entityHit, EntityDamageEvent::CAUSE_PROJECTILE, $damage);
        }

        $entityHit->attack($ev);
        $entityHit->setMotion($this->getOwningEntity()->getDirectionVector()->multiply(-0.3)->add(0, 0.3, 0));

        $this->isCollided = true;
        $this->flagForDespawn();
    }

    /**
     * @return int
     */
    public function getResultDamage(): int
    {
        return 1;
    }

    /**
     * @param int $tickDiff
     * @return bool
     */
    public function entityBaseTick(int $tickDiff = 1): bool
    {
        $hasUpdate = parent::entityBaseTick($tickDiff);
        $owner = $this->getOwningEntity();
        if ($owner instanceof Player) {
            if (!$owner->isAlive() || $owner->isClosed() || !$owner->getInventory()->getItemInHand() instanceof FishingRod) {
                $this->flagForDespawn();
            }
        } else {
            $this->flagForDespawn();
        }
        return $hasUpdate;
    }

    public function applyGravity(): void
    {
        if ($this->isUnderwater()) {
            $this->motion->y += $this->gravity;
            return;
        }
        parent::applyGravity();
    }
}