<?php

namespace hcf\level\block;

use hcf\HCF;
use hcf\HCFPlayer;
use pocketmine\block\Block;
use pocketmine\block\Transparent;
use pocketmine\level\Position;
use pocketmine\network\mcpe\protocol\ChangeDimensionPacket;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\Player;
use pocketmine\scheduler\Task;

class EndPortal extends Transparent {

    /** @var int $id */
    protected $id = Block::END_PORTAL;

    /**
     * Portal constructor.
     *
     * @param int $meta
     */
    public function __construct($meta = 0) {
        $this->meta = $meta;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return "End Portal";
    }

    /**
     * @return float
     */
    public function getHardness(): float {
        return -1;
    }

    /**
     * @return float
     */
    public function getResistance(): float {
        return 0;
    }

    /**
     * @return bool
     */
    public function canPassThrough(): bool {
        return true;
    }

    /**
     * @return bool
     */
    public function hasEntityCollision(): bool {
        return true;
    }

    /**
     * @param Item $item
     *
     * @return bool
     */
    public function isBreakable(Item $item): bool {
        return false;
    }

    /**
     * @param Item $item
     * @param Block $block
     * @param Block $target
     * @param int $face
     * @param Vector3 $facePos
     * @param Player|null $player
     *
     * @return bool
     */
    public function place(Item $item, Block $block, Block $target, int $face, Vector3 $facePos, Player $player = null): bool {
        if($player instanceof HCFPlayer) {
            $this->meta = $player->getDirection() & 0x01;
        }
        $this->getLevel()->setBlock($block, $this, true, true);
        return true;
    }

    /**
     * @param Item $item
     *
     * @return array
     */
    public function getDrops(Item $item): array {
        return [];
    }

    /**
     * @param Entity $entity
     */
    public function onEntityCollide(Entity $entity): void {
        if(!$entity instanceof HCFPlayer) {
            return;
        }
        $server = HCF::getInstance()->getServer();
        if($entity->isChangingDimension() === true) {
            return;
        }
        if($entity->getLevel()->getName() === $server->getDefaultLevel()->getName()) {
            $spawn = $server->getLevelByName("ender")->getSpawnLocation();
        }
        else {
            $spawn = $server->getDefaultLevel()->getSpawnLocation();
        }
        if($entity->isTagged() === true) {
            $entity->knockBack($entity, 0, -$entity->getDirectionVector()->getX(), -$entity->getDirectionVector()->getZ(), 1);
            return;
        }
        $entity->setChangingDimensions(true);
        HCF::getInstance()->getScheduler()->scheduleDelayedTask(new class($spawn, $entity) extends Task {

            /** @var Position */
            private $position;

            /** @var HCFPlayer */
            private $player;

            /**
             *  constructor.
             *
             * @param Position $position
             * @param HCFPlayer $player
             */
            public function __construct(Position $position, HCFPlayer $player) {
                $this->position = $position;
                $this->player = $player;
            }

            /**
             * @param int $currentTick
             */
            public function onRun(int $currentTick) {
                if(!$this->player->isOnline()) {
                    return;
                }
                if($this->player->getLevel()->getBlock($this->player)->getId() !== Block::END_PORTAL) {
                    $this->player->setChangingDimensions(false);
                    return;
                }
                $dimension = DimensionIds::THE_END;
                if($this->player->getLevel()->getFolderName() === "ender") {
                    $dimension = DimensionIds::OVERWORLD;
                }
                $pk = new ChangeDimensionPacket();
                $pk->dimension = $dimension;
                $pk->respawn = false;
                $pk->position = $this->position;
                $this->player->dataPacket($pk);
                $this->player->sendPlayStatus(PlayStatusPacket::PLAYER_SPAWN);
                $this->player->teleport($this->position);
                $this->player->setChangingDimensions(false);
            }
        }, 80);
    }
}