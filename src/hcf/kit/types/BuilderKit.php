<?php

namespace hcf\kit\types;

use hcf\kit\Kit;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;

class BuilderKit extends Kit {

    /** @var Item[] */
    private $items;

    /**
     * BuilderKit constructor.
     */
    public function __construct() {
        parent::__construct("Builder", 172800);
        $this->items = [
            ItemFactory::get(self::STONE_BUTTON, 0, 128),
            ItemFactory::get(self::REPEATER, 0, 64),
            ItemFactory::get(self::COMPARATOR, 0, 64),
            ItemFactory::get(self::REDSTONE_BLOCK, 0, 48),
            ItemFactory::get(self::STICKY_PISTON, 0, 64),
            ItemFactory::get(self::PISTON, 0, 64),
            ItemFactory::get(self::STONE, 0, 256),
            ItemFactory::get(self::GRASS, 0, 256),
            ItemFactory::get(self::WOOD, 0, 64),
            ItemFactory::get(self::FENCE_GATE, 0, 32),
            ItemFactory::get(self::BUCKET, Block::FLOWING_WATER, 2),
            ItemFactory::get(self::STRING, 0, 64),
            ItemFactory::get(self::REDSTONE_TORCH, 0, 64)
        ];
    }

    /**
     * @return Item[]
     */
    public function getItems(): array {
        return $this->items;
    }
}
