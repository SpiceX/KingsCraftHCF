<?php

namespace hcf\level\block;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\event\block\BlockGrowEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\math\Vector3;

class MelonStem extends Crops {

    /** @var int */
    protected $id = self::MELON_STEM;

    /**
     * @return string
     */
    public function getName(): string {
        return "Melon Stem";
    }

    /**
     * MelonStem constructor.
     *
     * @param int $meta
     */
    public function __construct(int $meta = 0) {
        $this->meta = $meta;
    }

    public function onRandomTick(): void {
        if($this->meta < 0x07) {
            $block = clone $this;
            ++$block->meta;
            $ev = new BlockGrowEvent($this, $block);
            $ev->call();
            if(!$ev->isCancelled()) {
                $this->getLevel()->setBlock($this, $ev->getNewState(), true);
            }
        }
        else {
            for($side = 2; $side <= 5; ++$side) {
                $b = $this->getSide($side);
                if($b->getId() === self::MELON_BLOCK) {
                    return;
                }
            }
            $side = $this->getSide(mt_rand(2, 5));
            $d = $side->getSide(Vector3::SIDE_DOWN);
            if($side->getId() === self::AIR and ($d->getId() === self::FARMLAND or $d->getId() === self::GRASS or $d->getId() === self::DIRT)) {
                $ev = new BlockGrowEvent($side, BlockFactory::get(Block::MELON_BLOCK));
                $ev->call();
                if(!$ev->isCancelled()) {
                    $this->getLevel()->setBlock($side, $ev->getNewState(), true);
                }
            }
        }
    }

    /**
     * @param Item $item
     *
     * @return array
     */
    public function getDropsForCompatibleTool(Item $item): array {
        return [
            ItemFactory::get(Item::MELON_SEEDS, 0, mt_rand(0, 2))
        ];
    }

    /**
     * @return Item
     */
    public function getPickedItem(): Item {
        return ItemFactory::get(Item::MELON_SEEDS);
    }
}