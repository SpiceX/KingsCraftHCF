<?php

namespace hcf\item\entity;

use hcf\HCFPlayer;
use hcf\item\CustomItem;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\RayTraceResult;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\Random;

class GrapplingHook extends Projectile
{

    /** @var int */
    public const NETWORK_ID = self::FISHING_HOOK;

    /** @var float */
    public $height = 0.25;

    /** @var float */
    public $width = 0.25;

    /** @var float */
    protected $gravity = 0.1;

    /**
     * GrapplingHook constructor.
     *
     * @param Level $level
     * @param CompoundTag $nbt
     * @param Entity|null $owner
     */
    public function __construct(Level $level, CompoundTag $nbt, ?Entity $owner = null)
    {
        parent::__construct($level, $nbt, $owner);
        if ($owner instanceof HCFPlayer) {
            $this->setPosition($this->add(0, $owner->getEyeHeight() - 0.1));
            $this->setMotion($owner->getDirectionVector()->multiply(0.4));
            $owner->setGrapplingHook($this);
            $this->handleHookCasting($this->motion->x, $this->motion->y, $this->motion->z, 1.5, 1.0);
        }
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
        $x = $x + $rand->nextSignedFloat() * 0.007499999832361937 * $f2;
        $y = $y + $rand->nextSignedFloat() * 0.007499999832361937 * $f2;
        $z = $z + $rand->nextSignedFloat() * 0.007499999832361937 * $f2;
        $x *= $f1;
        $y *= $f1;
        $z *= $f1;
        $this->motion->x += $x;
        $this->motion->y += $y;
        $this->motion->z += $z;
    }

    /**
     * @param int $tickDiff
     *
     * @return bool
     */
    public function entityBaseTick(int $tickDiff = 1): bool
    {
        $hasUpdate = parent::entityBaseTick($tickDiff);
        $owner = $this->getOwningEntity();
        if ($owner instanceof HCFPlayer) {
            if ($owner->getInventory()->getItemInHand()->getId() !== Item::FISHING_ROD || $owner->getInventory()->getItemInHand()->getNamedTagEntry(CustomItem::CUSTOM) === null or !$owner->isAlive() or $owner->isClosed()) {
                $this->flagForDespawn();
            }
        } else {
            $this->flagForDespawn();
        }
        return $hasUpdate;
    }

    public function close(): void
    {
        parent::close();
        $owner = $this->getOwningEntity();
        if ($owner instanceof HCFPlayer) {
            $owner->setGrapplingHook($this);
        }
    }

    /**
     * @return int
     */
    public function getResultDamage(): int
    {
        return 1;
    }

    public function handleHookRetraction(): void
    {
        $owner = $this->getOwningEntity();
        $owner->setMotion($this->subtract($owner)->multiply(0.2));
    }

    public function applyGravity(): void
    {
        if ($this->isUnderwater()) {
            $this->motion->y += $this->gravity;
        } else {
            parent::applyGravity();
        }
    }
}