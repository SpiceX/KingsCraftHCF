<?php

namespace hcf\item;

use hcf\item\entity\GrapplingHook;
use pocketmine\item\Durable;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\level\sound\LaunchSound;
use pocketmine\math\Vector3;
use pocketmine\Player;


class FishingRod extends Durable
{
    /** @var GrapplingHook $hook */
    private $hook;

    /**
     * FishingRod constructor.
     * @param int $meta
     */
    public function __construct($meta = 0)
    {
        parent::__construct(Item::FISHING_ROD, $meta, 'Fishing Rod');
    }

    /**
     * @return int
     */
    public function getMaxStackSize(): int
    {
        return 1;
    }

    /**
     * @return int
     */
    public function getMaxDurability(): int
    {
        return 65;
    }

    /**
     * @return int
     */
    public function getFuelTime(): int
    {
        return 300;
    }

    /**
     * @param Entity $victim
     * @return bool
     */
    public function onAttackEntity(Entity $victim): bool
    {
        return $this->applyDamage(1);
    }

    /**
     * @param Player $player
     * @param Vector3 $directionVector
     * @return bool
     */
    public function onClickAir(Player $player, Vector3 $directionVector): bool
    {
        if ($this->hook === null) {
            $nbt = Entity::createBaseNBT($player);
            $this->hook = Entity::createEntity('FishingHook', $player->level, $nbt, $player);
            $this->hook->spawnToAll();
            $player->getLevel()->addSound(new LaunchSound($player), $player->getViewers());
        } else {
            if (!$this->hook->isFlaggedForDespawn()) {
                $this->hook->flagForDespawn();
            }
            $this->hook = null;
        }
        return true;
    }
}