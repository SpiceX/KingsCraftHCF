<?php

namespace hcf\kit\types;

use hcf\kit\Kit;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;

class StarterKit extends Kit {

    /** @var Item[] */
    private $items;

    /**
     * StarterKit constructor.
     */
    public function __construct() {
        parent::__construct("Starter", 86400);
        $this->items = [
            ItemFactory::get(self::BAKED_POTATO, 0, 64),
            ItemFactory::get(self::NETHER_WART, 0, 8),
            ItemFactory::get(self::BLAZE_POWDER, 0, 8),
            ItemFactory::get(self::GLISTERING_MELON, 0, 8),
            ItemFactory::get(self::MAGMA_CREAM, 0, 8),
            ItemFactory::get(self::FERMENTED_SPIDER_EYE, 0, 8),
            ItemFactory::get(self::SUGAR, 0, 8),
            ItemFactory::get(self::GUNPOWDER, 0, 8),
            ItemFactory::get(self::GLOWSTONE_DUST, 0, 8),
            ItemFactory::get(self::POTATO, 0, 16),
            ItemFactory::get(self::MELON_SEEDS, 0, 16),
            ItemFactory::get(self::WHEAT_SEEDS, 0, 16),
            ItemFactory::get(self::SUGARCANE, 0, 16)
        ];
    }

    /**
     * @return Item[]
     */
    public function getItems(): array {
        return $this->items;
    }
}
