<?php

namespace hcf\kit\types;

use hcf\HCF;
use hcf\item\CustomItem;
use hcf\kit\Kit;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\Potion;
use pocketmine\utils\TextFormat;

class BardKit extends Kit {

    /** @var Item[] */
    private $items;

    /**
     * BardKit constructor.
     */
    public function __construct() {
        parent::__construct("Bard", 259200);
        $name = TextFormat::RESET . TextFormat::BOLD . TextFormat::GOLD . $this->getName() . " " . TextFormat::RESET . TextFormat::YELLOW;
        $this->items = [
            (new CustomItem(self::GOLD_HELMET, $name . "Helmet", [], [
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PROTECTION), HCF::MAX_PROTECTION),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 3)
            ]))->getItemForm(),
            (new CustomItem(self::GOLD_CHESTPLATE, $name . "Chestplate", [], [
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PROTECTION), HCF::MAX_PROTECTION),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 3)
            ]))->getItemForm(),
            (new CustomItem(self::GOLD_LEGGINGS, $name . "Leggings", [], [
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PROTECTION), HCF::MAX_PROTECTION),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 3)
            ]))->getItemForm(),
            (new CustomItem(self::GOLD_BOOTS, $name . "Boots", [], [
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PROTECTION), HCF::MAX_PROTECTION),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 3),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::FEATHER_FALLING), 4)
            ]))->getItemForm(),
            (new CustomItem(self::DIAMOND_SWORD, $name . "Sword", [], [
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::SHARPNESS), HCF::MAX_SHARPNESS),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 3)
            ]))->getItemForm(),
            ItemFactory::get(self::BAKED_POTATO, 0, 64),
            ItemFactory::get(self::ENDER_PEARL, 0, 16),
            ItemFactory::get(self::BLAZE_POWDER, 0, 64),
            ItemFactory::get(self::SUGAR, 0, 64),
            ItemFactory::get(self::IRON_INGOT, 0, 64),
            ItemFactory::get(self::GHAST_TEAR, 0, 16),
            ItemFactory::get(self::FEATHER, 0, 64),
            ItemFactory::get(self::SPIDER_EYE, 0, 64),
            ItemFactory::get(self::MAGMA_CREAM, 0, 64),
            ItemFactory::get(self::SPLASH_POTION, Potion::STRONG_HEALING, 26)
        ];
    }

    /**
     * @return Item[]
     */
    public function getItems(): array {
        return $this->items;
    }
}
