<?php

namespace hcf\kit\types;

use hcf\kit\Kit;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;

class FoodKit extends Kit {

    /** @var Item[] */
    private $items;

    /**
     * FoodKit constructor.
     */
    public function __construct() {
        parent::__construct("Food", 1800);
        $this->items = [
            ItemFactory::get(self::BAKED_POTATO, 0, 64)
        ];
    }

    /**
     * @return Item[]
     */
    public function getItems(): array {
        return $this->items;
    }
}
