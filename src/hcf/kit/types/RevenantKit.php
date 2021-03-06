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

class RevenantKit extends Kit {

    /** @var Item[] */
    private $items;

    /**
     * RevenantKit constructor.
     */
    public function __construct() {
        parent::__construct("Revenant", 86400);
        $name = TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_RED . $this->getName() . " " . TextFormat::RESET . TextFormat::RED;
        $this->items = [
            (new CustomItem(self::DIAMOND_HELMET, $name . "Helmet", [], [
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PROTECTION), HCF::MAX_PROTECTION),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 2)
            ]))->getItemForm(),
            (new CustomItem(self::DIAMOND_CHESTPLATE, $name . "Chestplate", [], [
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PROTECTION), HCF::MAX_PROTECTION),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 2)
            ]))->getItemForm(),
            (new CustomItem(self::DIAMOND_LEGGINGS, $name . "Leggings", [], [
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PROTECTION), HCF::MAX_PROTECTION),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 2)
            ]))->getItemForm(),
            (new CustomItem(self::DIAMOND_BOOTS, $name . "Boots", [], [
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PROTECTION), HCF::MAX_PROTECTION),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 2),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::FEATHER_FALLING), 2)
            ]))->getItemForm(),
            (new CustomItem(self::DIAMOND_SWORD, $name . "Sword", [], [
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::SHARPNESS), HCF::MAX_SHARPNESS - 1),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 2)
            ]))->getItemForm(),
            ItemFactory::get(self::BAKED_POTATO, 0, 64),
            ItemFactory::get(self::ENDER_PEARL, 0, 16),
        ];
    }

    /**
     * @return Item[]
     */
    public function getItems(): array {
        return $this->items;
    }
}
