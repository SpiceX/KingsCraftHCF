<?php

namespace hcf\kit\types;

use hcf\HCF;
use hcf\item\CustomItem;
use hcf\kit\Kit;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\utils\TextFormat;

class KingHeroKit extends Kit {

    /** @var Item[] */
    private $items;

    /**
     * KingHeroKit constructor.
     */
    public function __construct() {
        parent::__construct("KingHero", 86400);
        $name = TextFormat::RESET . TextFormat::BOLD . TextFormat::BLUE . $this->getName() . " " . TextFormat::RESET . TextFormat::DARK_BLUE;
        $this->items = [
            (new CustomItem(self::DIAMOND_HELMET, $name . "Helmet", [], [
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PROTECTION), HCF::MAX_PROTECTION - 1),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 2)
            ]))->getItemForm(),
            (new CustomItem(self::DIAMOND_CHESTPLATE, $name . "Chestplate", [], [
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PROTECTION), HCF::MAX_PROTECTION - 1),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 2)
            ]))->getItemForm(),
            (new CustomItem(self::DIAMOND_LEGGINGS, $name . "Leggings", [], [
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PROTECTION), HCF::MAX_PROTECTION - 1),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 2)
            ]))->getItemForm(),
            (new CustomItem(self::DIAMOND_BOOTS, $name . "Boots", [], [
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PROTECTION), HCF::MAX_PROTECTION - 1),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 2),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::FEATHER_FALLING), 1)
            ]))->getItemForm(),
            (new CustomItem(self::DIAMOND_SWORD, $name . "Sword", [], [
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::SHARPNESS), HCF::MAX_SHARPNESS - 1),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 2)
            ]))->getItemForm(),
            ItemFactory::get(self::BAKED_POTATO, 0, 64),
            ItemFactory::get(self::ENDER_PEARL, 0, 16)
        ];
    }

    /**
     * @return Item[]
     */
    public function getItems(): array {
        return $this->items;
    }
}
