<?php

namespace hcf\level\block;

use pocketmine\item\Item;
use pocketmine\item\ItemFactory;

class Carrots extends Crops {

    /** @var int */
    protected $id = self::CARROT_BLOCK;

    /**
     * Carrots constructor.
     *
     * @param int $meta
     */
    public function __construct(int $meta = 0) {
        $this->meta = $meta;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return "Carrot Block";
    }

    /**
     * @param Item $item
     *
     * @return array
     */
    public function getDropsForCompatibleTool(Item $item): array {
        return [
            ItemFactory::get(Item::CARROT, 0, $this->meta >= 0x07 ? mt_rand(1, 4) : 1)
        ];
    }

    /**
     * @return Item
     */
    public function getPickedItem(): Item {
        return ItemFactory::get(Item::CARROT);
    }
}