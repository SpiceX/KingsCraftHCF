<?php

namespace hcf\level\tile;

use hcf\HCF;
use hcf\level\inventory\BeaconInventory;
use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\inventory\InventoryHolder;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\Player;
use pocketmine\Server as PMServer;
use pocketmine\tile\Spawnable;

class Beacon extends Spawnable implements InventoryHolder {

    /** @var string */
    public const
        TAG_PRIMARY = "primary",
        TAG_SECONDARY = "secondary";

    /** @var BeaconInventory */
    private $inventory;

    /** @var CompoundTag */
    private $nbt;

    /**
     * Beacon constructor.
     *
     * @param Level $level
     * @param CompoundTag $nbt
     */
    public function __construct(Level $level, CompoundTag $nbt) {
        if(!$nbt->hasTag(self::TAG_PRIMARY, IntTag::class)) {
            $nbt->setInt(self::TAG_PRIMARY, 0);
        }
        if(!$nbt->hasTag(self::TAG_SECONDARY, IntTag::class)) {
            $nbt->setInt(self::TAG_SECONDARY, 0);
        }
        $this->inventory = new BeaconInventory($this);
        parent::__construct($level, $nbt);
        $this->scheduleUpdate();
    }

    /**
     * @param CompoundTag $nbt
     */
    public function addAdditionalSpawnData(CompoundTag $nbt): void {
        $nbt->setInt(self::TAG_PRIMARY, $this->getNBT()->getInt(self::TAG_PRIMARY));
        $nbt->setInt(self::TAG_SECONDARY, $this->getNBT()->getInt(self::TAG_SECONDARY));
    }

    /**
     * @return CompoundTag
     */
    public function getNBT(): CompoundTag {
        return $this->nbt;
    }

    /**
     * @param CompoundTag $nbt
     * @param Player $player
     *
     * @return bool
     */
    public function updateCompoundTag(CompoundTag $nbt, Player $player): bool {
        $this->setPrimaryEffect($nbt->getInt(self::TAG_PRIMARY));
        $this->setSecondaryEffect($nbt->getInt(self::TAG_SECONDARY));
        return true;
    }

    /**
     * @param int $effectId
     */
    public function setPrimaryEffect(int $effectId) {
        $this->getNBT()->setInt(self::TAG_PRIMARY, $effectId);
    }

    /**
     * @param int $effectId
     */
    public function setSecondaryEffect(int $effectId) {
        $this->getNBT()->setInt(self::TAG_SECONDARY, $effectId);
    }

    /**
     * @param Item $item
     *
     * @return bool
     */
    public function isPaymentItem(Item $item) {
        return in_array($item->getId(), [Item::DIAMOND, Item::IRON_INGOT, Item::GOLD_INGOT, Item::EMERALD]);
    }

    /**
     * @return bool
     */
    public function isSecondaryAvailable() {
        return $this->getLayers() >= 4 && !$this->solidAbove();
    }

    /**
     * @return int
     */
    public function getLayers() {
        $layers = 0;
        if($this->checkShape($this->getSide(0), 1)) {
            $layers++;
        }
        else {
            return $layers;
        }
        if($this->checkShape($this->getSide(0, 2), 2)) {
            $layers++;
        }
        else {
            return $layers;
        }
        if($this->checkShape($this->getSide(0, 3), 3)) {
            $layers++;
        }
        else {
            return $layers;
        }
        if($this->checkShape($this->getSide(0, 4), 4)) {
            $layers++;
        }
        return $layers;
    }

    /**
     * @param Vector3 $pos
     * @param int $layer
     *
     * @return bool
     */
    public function checkShape(Vector3 $pos, $layer = 1) {
        for($x = $pos->x - $layer; $x <= $pos->x + $layer; $x++) {
            for($z = $pos->z - $layer; $z <= $pos->z + $layer; $z++) {
                if(!in_array($this->getLevel()->getBlockIdAt($x, $pos->y, $z), [Block::DIAMOND_BLOCK, Block::IRON_BLOCK, Block::EMERALD_BLOCK, Block::GOLD_BLOCK])) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @return bool
     */
    public function solidAbove() {
        if($this->y === $this->getLevel()->getHighestBlockAt($this->x, $this->z)) {
            return false;
        }
        for($i = $this->y; $i < Level::Y_MAX; $i++) {
            if(($block = $this->getLevel()->getBlock(new Vector3($this->x, $i, $this->z)))->isSolid() && !$block->getId() === Block::BEACON) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isActive() {
        return !empty($this->getEffects()) and $this->checkShape($this->getSide(0), 1);
    }

    /**
     * @return array
     */
    public function getEffects() {
        return [$this->getPrimaryEffect(), $this->getSecondaryEffect()];
    }

    /**
     * @return int
     */
    public function getPrimaryEffect() {
        return $this->getNBT()->getInt(self::TAG_PRIMARY);
    }

    /**
     * @return int
     */
    public function getSecondaryEffect() {
        return $this->getNBT()->getInt(self::TAG_SECONDARY);
    }

    public function getTierEffects() {
    }

    /**
     * @param int $tier
     */
    public function getEffectTier(int $tier) {
    }

    /**
     * @return bool
     */
    public function onUpdate(): bool {
        if($this->level instanceof Level) {
            if(!PMServer::getInstance()->isLevelLoaded($this->level->getName()) || !$this->level->isChunkLoaded($this->x >> 4, $this->z >> 4)) {
                return false;
            }
            $claim = HCF::getInstance()->getFactionManager()->getClaimInPosition($this->asPosition());
            if($claim !== null) {
                if(!empty($this->getEffects())) {
                    $this->applyEffects($this);
                }
            }
            else {
                $this->level->setBlock($this, new Air(), true, true);
                $this->close();
            }
        }
        return true;
    }

    /**
     * @param Vector3 $pos
     */
    public function applyEffects(Vector3 $pos) {
        $layers = $this->getLayers();
        /** @var Player $player */
        foreach($this->getLevel()->getCollidingEntities(new AxisAlignedBB($pos->x - (10 + 10 * $layers), 0, $pos->z - (10 + 10 * $layers), $pos->x + (10 + 10 * $layers), Level::Y_MAX, $pos->z + (10 + 10 * $layers))) as $player) {
            foreach($this->getEffects() as $effectId) {
                if($this->isEffectAvailable($effectId) and $player instanceof Player) {
                    $player->removeEffect($effectId);//Pretty hacky..
                    $eff = new EffectInstance(Effect::getEffect($effectId));
                    $effect = $eff->setDuration(20 * 9 + $layers * 2 * 20);
                    if($this->getSecondaryEffect() !== 0 && $this->getSecondaryEffect() !== Effect::REGENERATION) {
                        $effect->setAmplifier(1);
                    }
                    $player->addEffect($effect);
                }
            }
        }
    }

    /**
     * @param int $effectId
     *
     * @return bool
     */
    public function isEffectAvailable(int $effectId) {
        switch($effectId) {
            case Effect::SPEED:
            case Effect::HASTE:
                return $this->getLayers() >= 1 && !$this->solidAbove();
                break;
            case Effect::DAMAGE_RESISTANCE:
            case Effect::JUMP:
                return $this->getLayers() >= 2 && !$this->solidAbove();
                break;
            case Effect::STRENGTH:
                return $this->getLayers() >= 3 && !$this->solidAbove();
                break;
            case Effect::REGENERATION:
                //this case is for secondary effect only
                return $this->getLayers() >= 4 && !$this->solidAbove();
                break;
            default:
                return false;
        }
    }

    /**
     * Get the object related inventory
     *
     * @return BeaconInventory
     */
    public function getInventory() {
        return $this->inventory;
    }

    /**
     * @param CompoundTag $nbt
     */
    protected function readSaveData(CompoundTag $nbt): void {
        $this->nbt = $nbt;
    }

    /**
     * @param CompoundTag $nbt
     */
    protected function writeSaveData(CompoundTag $nbt): void {
        $nbt->setInt(self::TAG_PRIMARY, $this->getNBT()->getInt(self::TAG_PRIMARY));
        $nbt->setInt(self::TAG_SECONDARY, $this->getNBT()->getInt(self::TAG_SECONDARY));
    }
}