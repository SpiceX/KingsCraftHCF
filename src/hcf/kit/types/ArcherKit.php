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

class ArcherKit extends Kit {

    /** @var Item[] */
    private $items;

    /**
     * ArcherKit constructor.
     */
    public function __construct() {
        parent::__construct("Archer", 259200);
        $name = TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_GREEN . $this->getName() . " " . TextFormat::RESET . TextFormat::GREEN;
        $this->items = [
            (new CustomItem(self::LEATHER_CAP, $name . "Helmet", [], [
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PROTECTION), HCF::MAX_PROTECTION),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 3)
            ]))->getItemForm(),
            (new CustomItem(self::LEATHER_CHESTPLATE, $name . "Chestplate", [], [
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PROTECTION), HCF::MAX_PROTECTION),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 3)
            ]))->getItemForm(),
            (new CustomItem(self::LEATHER_PANTS, $name . "Leggings", [], [
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PROTECTION), HCF::MAX_PROTECTION),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 3)
            ]))->getItemForm(),
            (new CustomItem(self::LEATHER_BOOTS, $name . "Boots", [], [
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PROTECTION), HCF::MAX_PROTECTION),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 3),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::FEATHER_FALLING), 4)
            ]))->getItemForm(),
            (new CustomItem(self::DIAMOND_SWORD, $name . "Sword", [], [
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::SHARPNESS), HCF::MAX_SHARPNESS),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 3)
            ]))->getItemForm(),
            (new CustomItem(self::BOW, $name . "Bow", [], [
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::POWER), HCF::MAX_POWER - 1),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PUNCH), 2),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::FLAME), 1),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 3),
            ]))->getItemForm(),
            ItemFactory::get(self::BAKED_POTATO, 0, 64),
            ItemFactory::get(self::ARROW, 0, 128),
            ItemFactory::get(self::SUGAR, 0, 64),
            ItemFactory::get(self::ENDER_PEARL, 0, 16),
            ItemFactory::get(self::SPLASH_POTION, Potion::STRONG_HEALING, 29)
        ];
    }

    /**
     * @return Item[]
     */
    public function getItems(): array {
        return $this->items;
    }
}
