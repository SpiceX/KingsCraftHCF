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

class DiamondKit extends Kit {

    /** @var Item[] */
    private $items;

    /**
     * DiamondKit constructor.
     */
    public function __construct() {
        parent::__construct("Diamond", 259200);
        $name = TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . $this->getName() . " " . TextFormat::RESET . TextFormat::DARK_AQUA;
        $this->items = [
            (new CustomItem(self::DIAMOND_HELMET, $name . "Helmet", [], [
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PROTECTION), HCF::MAX_PROTECTION),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 3)
            ]))->getItemForm(),
            (new CustomItem(self::DIAMOND_CHESTPLATE, $name . "Chestplate", [], [
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PROTECTION), HCF::MAX_PROTECTION),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 3)
            ]))->getItemForm(),
            (new CustomItem(self::DIAMOND_LEGGINGS, $name . "Leggings", [], [
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PROTECTION), HCF::MAX_PROTECTION),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 3)
            ]))->getItemForm(),
            (new CustomItem(self::DIAMOND_BOOTS, $name . "Boots", [], [
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PROTECTION), HCF::MAX_PROTECTION),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 3),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::FEATHER_FALLING), 4)
            ]))->getItemForm(),
            (new CustomItem(self::DIAMOND_SWORD, $name . "Sword", [], [
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::SHARPNESS), HCF::MAX_SHARPNESS),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::LOOTING), 4),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 3)
            ]))->getItemForm(),
            ItemFactory::get(self::BAKED_POTATO, 0, 64),
            ItemFactory::get(self::ENDER_PEARL, 0, 16),
            ItemFactory::get(self::SPLASH_POTION, Potion::STRONG_HEALING, 25),
            ItemFactory::get(self::POTION, Potion::LONG_INVISIBILITY, 1),
            ItemFactory::get(self::POTION, Potion::LONG_FIRE_RESISTANCE, 1),
            ItemFactory::get(self::POTION, Potion::STRONG_SWIFTNESS, 4),
        ];
    }

    /**
     * @return Item[]
     */
    public function getItems(): array {
        return $this->items;
    }
}
