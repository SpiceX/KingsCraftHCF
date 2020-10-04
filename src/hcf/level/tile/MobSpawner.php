<?php

namespace hcf\level\tile;

use hcf\HCFPlayer;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityIds;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\tile\Spawnable;
use ReflectionClass;
use ReflectionException;

class MobSpawner extends Spawnable {

    public const NBT_KEY_SPAWNER_IS_MOVABLE = "isMovable";

    public const NBT_KEY_SPAWNER_DELAY = "Delay";

    public const NBT_KEY_SPAWNER_MAX_NEARBY_ENTITIES = "MaxNearbyEntities";

    public const NBT_KEY_SPAWNER_MAX_SPAWN_DELAY = "MaxSpawnDelay";

    public const NBT_KEY_SPAWNER_MIN_SPAWN_DELAY = "MinSawnDelay";

    public const NBT_KEY_SPAWNER_REQUIRED_PLAYER_RANGE = "RequiredPlayerRange";

    public const NBT_KEY_SPAWNER_SPAWN_COUNT = "SpawnCount";

    public const NBT_KEY_SPAWNER_SPAWN_RANGE = "SpawnRange";

    public const NBT_KEY_SPAWNER_ENTITY_ID = "EntityId";

    public const NBT_KEY_SPAWNER_DISPLAY_ENTITY_HEIGHT = "DisplayEntityHeight";

    public const NBT_KEY_SPAWNER_DISPLAY_ENTITY_SCALE = "DisplayEntityScale";

    public const NBT_KEY_SPAWNER_DISPLAY_ENTITY_WIDTH = "DisplayEntityWidth";

    public const NBT_KEY_SPAWNER_SPAWN_DATA = "SpawnData";

    /** @var int */
    public $entityId = -1;

    /** @var int */
    protected $spawnRange = 4;

    /** @var int */
    protected $maxNearbyEntities = 6;

    /** @var int */
    protected $requiredPlayerRange = 16;

    /** @var int */
    protected $delay = 0;

    /** @var int */
    protected $minSpawnDelay = 300;

    /** @var int */
    protected $maxSpawnDelay = 800;

    /** @var int */
    protected $spawnCount = 1;

    /**
     * MobSpawner constructor.
     *
     * @param Level $level
     * @param CompoundTag $nbt
     */
    public function __construct(Level $level, CompoundTag $nbt) {
        parent::__construct($level, $nbt);
        $this->scheduleUpdate();
    }

    /**
     * @return bool
     * @throws ReflectionException
     */
    public function onUpdate(): bool {
        if($this->isClosed()) {
            return false;
        }
        if($this->getLevel()->getBlock($this)->getId() !== Block::MOB_SPAWNER) {
            $this->close();
            return false;
        }
        if($this->entityId === -1) {
            return false;
        }
        if(--$this->delay <= 0 and $this->canUpdate() === true) {
            $success = false;
            for($i = 0; $i < $this->getSpawnCount(); $i++) {
                $pos = $this->add(mt_rand() / mt_getrandmax() * $this->spawnRange, mt_rand(-1, 1), mt_rand() / mt_getrandmax() * $this->spawnRange);
                $target = $this->getLevel()->getBlock($pos);
                if($target->getId() === Item::AIR) {
                    $success = true;
                    $entity = Entity::createEntity($this->getEntityType(), $this->getLevel(), Entity::createBaseNBT($target->add(0.5, 0, 0.5), null, lcg_value() * 360, 0));
                    if($entity instanceof Entity) {
                        $entity->spawnToAll();
                    }
                }
            }
            if($success) {
                $this->delay = mt_rand($this->minSpawnDelay, $this->maxSpawnDelay);
            }
        }
        return true;
    }

    public function canUpdate(): bool{
        if($this->entityId !== 0 and $this->getLevel()->isChunkLoaded($this->getX() >> 4, $this->getZ() >> 4)){
            $hasPlayer = false;
            $count = 0;
            foreach($this->getLevel()->getEntities() as $e){
                if($e instanceof HCFPlayer and $e->distance($this) <= $this->requiredPlayerRange){
                    $hasPlayer = true;
                }
                if($e::NETWORK_ID == $this->entityId){
                    $count++;
                }
            }
            return ($hasPlayer and $count < $this->maxNearbyEntities);
        }
        return false;
    }

    /**
     * @param int $entityId
     */
    public function setSpawnEntityType(int $entityId) {
        $this->entityId = $entityId;
        $this->writeSaveData($tag = new CompoundTag());
        $this->onChanged();
        $this->scheduleUpdate();
    }

    /**
     * @param int $minDelay
     */
    public function setMinSpawnDelay(int $minDelay) {
        if($minDelay > $this->maxSpawnDelay) {
            return;
        }
        $this->minSpawnDelay = $minDelay;
    }

    /**
     * @param int $maxDelay
     */
    public function setMaxSpawnDelay(int $maxDelay) {
        if($this->minSpawnDelay > $maxDelay or $maxDelay === 0) {
            return;
        }
        $this->maxSpawnDelay = $maxDelay;
    }

    /**
     * @param int $minDelay
     * @param int $maxDelay
     */
    public function setSpawnDelay(int $minDelay, int $maxDelay) {
        if($minDelay > $maxDelay) {
            return;
        }
        $this->minSpawnDelay = $minDelay;
        $this->maxSpawnDelay = $maxDelay;
    }

    /**
     * @param int $range
     */
    public function setRequiredPlayerRange(int $range) {
        $this->requiredPlayerRange = $range;
    }

    /**
     * @param int $count
     */
    public function setMaxNearbyEntities(int $count) {
        $this->maxNearbyEntities = $count;
    }

    /**
     * @param CompoundTag $nbt
     */
    public function addAdditionalSpawnData(CompoundTag $nbt): void {
        $nbt->setByte(self::NBT_KEY_SPAWNER_IS_MOVABLE, 1);
        $nbt->setShort(self::NBT_KEY_SPAWNER_DELAY, 0);
        $nbt->setShort(self::NBT_KEY_SPAWNER_MAX_NEARBY_ENTITIES, $this->maxNearbyEntities);
        $nbt->setShort(self::NBT_KEY_SPAWNER_MAX_SPAWN_DELAY, $this->maxSpawnDelay);
        $nbt->setShort(self::NBT_KEY_SPAWNER_MIN_SPAWN_DELAY, $this->minSpawnDelay);
        $nbt->setShort(self::NBT_KEY_SPAWNER_REQUIRED_PLAYER_RANGE, $this->requiredPlayerRange);
        $nbt->setShort(self::NBT_KEY_SPAWNER_SPAWN_COUNT, $this->spawnCount);
        $nbt->setShort(self::NBT_KEY_SPAWNER_SPAWN_RANGE, $this->spawnRange);
        $nbt->setInt(self::NBT_KEY_SPAWNER_ENTITY_ID, $this->entityId);
        $this->scheduleUpdate();
    }

    /**
     * @param CompoundTag $nbt
     */
    public function readSaveData(CompoundTag $nbt): void {
        if($nbt->hasTag(self::NBT_KEY_SPAWNER_ENTITY_ID)) {
            $this->setSpawnEntityType($nbt->getInt(self::NBT_KEY_SPAWNER_ENTITY_ID, -1, true));
        }
        if($nbt->hasTag(self::NBT_KEY_SPAWNER_SPAWN_RANGE)) {
            $this->spawnRange = $nbt->getShort(self::NBT_KEY_SPAWNER_SPAWN_RANGE, 4, true);
        }
        if($nbt->hasTag(self::NBT_KEY_SPAWNER_MIN_SPAWN_DELAY)) {
            $this->minSpawnDelay = $nbt->getShort(self::NBT_KEY_SPAWNER_MIN_SPAWN_DELAY, 200, true);
        }
        if($nbt->hasTag(self::NBT_KEY_SPAWNER_MAX_SPAWN_DELAY)) {
            $this->maxSpawnDelay = $nbt->getShort(self::NBT_KEY_SPAWNER_MAX_SPAWN_DELAY, 800, true);
        }
        if($nbt->hasTag(self::NBT_KEY_SPAWNER_DELAY)) {
            $this->delay = $nbt->getShort(self::NBT_KEY_SPAWNER_DELAY, 0, true);
        }
        if($nbt->hasTag(self::NBT_KEY_SPAWNER_MAX_NEARBY_ENTITIES)) {
            $this->maxNearbyEntities = $nbt->getShort(self::NBT_KEY_SPAWNER_MAX_NEARBY_ENTITIES, 6, true);
        }
        if($nbt->hasTag(self::NBT_KEY_SPAWNER_REQUIRED_PLAYER_RANGE)) {
            $this->requiredPlayerRange = $nbt->getShort(self::NBT_KEY_SPAWNER_REQUIRED_PLAYER_RANGE, 16);
        }
        if($nbt->hasTag(self::NBT_KEY_SPAWNER_SPAWN_COUNT)) {
            $this->spawnCount = $nbt->getShort(self::NBT_KEY_SPAWNER_SPAWN_COUNT, 2, true);
        }
    }

    /**
     * @param CompoundTag $nbt
     */
    public function writeSaveData(CompoundTag $nbt): void {
        $this->addAdditionalSpawnData($nbt);
    }

    /**
     * @return int
     */
    public function getSpawnCount(): int {
        return $this->spawnCount;
    }

    /**
     * @return string
     * @throws ReflectionException
     */
    public function getEntityType(): string {
        $class = new ReflectionClass(EntityIds::class);
        $ids = array_flip($class->getConstants());
        $id = $ids[$this->entityId];
        $name = implode("", explode(" ", ucwords(strtolower(implode(" ", explode("_", $id))))));
        return $name;
    }
}