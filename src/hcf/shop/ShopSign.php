<?php

namespace hcf\shop;

use pocketmine\item\Item;
use pocketmine\level\Position;

class ShopSign {

    const BUY = 0;

    const SELL = 1;

    /** @var Position */
    private $position;

    /** @var Item */
    private $item;

    /** @var int */
    private $price;

    /** @var int */
    private $type;

    /**
     * ShopSign constructor.
     *
     * @param Position $position
     * @param Item $item
     * @param int $price
     * @param int $type
     */
    public function __construct(Position $position, Item $item, int $price, int $type) {
        $this->position = $position;
        $this->item = $item;
        $this->price = $price;
        $this->type = $type;
    }

    /**
     * @return Position
     */
    public function getPosition(): Position {
        return $this->position;
    }

    /**
     * @return Item
     */
    public function getItem(): Item {
        return $this->item;
    }

    /**
     * @return int
     */
    public function getPrice(): int {
        return $this->price;
    }

    /**
     * @return int
     */
    public function getType(): int {
        return $this->type;
    }
}