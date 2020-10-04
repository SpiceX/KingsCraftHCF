<?php

namespace hcf\kit\types;

use hcf\HCF;
use hcf\item\CustomItem;
use hcf\kit\Kit;
use pocketmine\block\Block;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\utils\TextFormat;

class MinerKit extends Kit {

    /** @var Item[] */
    private $items;

    /**
     * MinerKit constructor.
     */
    public function __construct() {
        parent::__construct("Miner", 172800);
        $name = TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_AQUA . $this->getName() . " " . TextFormat::RESET . TextFormat::AQUA;
        $this->items = [
            (new CustomItem(self::IRON_HELMET, $name . "Helmet", [], [
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PROTECTION), HCF::MAX_PROTECTION),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 3)
            ]))->getItemForm(),
            (new CustomItem(self::IRON_CHESTPLATE, $name . "Chestplate", [], [
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PROTECTION), HCF::MAX_PROTECTION),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 3)
            ]))->getItemForm(),
            (new CustomItem(self::IRON_LEGGINGS, $name . "Leggings", [], [
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PROTECTION), HCF::MAX_PROTECTION),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 3)
            ]))->getItemForm(),
            (new CustomItem(self::IRON_BOOTS, $name . "Boots", [], [
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PROTECTION), HCF::MAX_PROTECTION),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 3),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::FEATHER_FALLING), 4)
            ]))->getItemForm(),
            (new CustomItem(self::DIAMOND_PICKAXE, $name . "Pickaxe", [], [
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 5),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::FORTUNE), 4),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 3)
            ]))->getItemForm(),
            (new CustomItem(self::DIAMOND_PICKAXE, $name . "Pickaxe", [], [
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 4),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::FORTUNE), 3),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 3)
            ]))->getItemForm(),
            (new CustomItem(self::DIAMOND_SHOVEL, $name . "Shovel", [], [
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 4),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 3)
            ]))->getItemForm(),
            (new CustomItem(self::DIAMOND_AXE, $name . "Axe", [], [
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 4),
                new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 3)
            ]))->getItemForm(),
            ItemFactory::get(self::BAKED_POTATO, 0, 64),
            ItemFactory::get(self::ANVIL, 0, 4),
            ItemFactory::get(self::CRAFTING_TABLE, 0, 4),
            ItemFactory::get(self::BUCKET, Block::FLOWING_WATER, 2)
        ];
    }

    /**
     * @return Item[]
     */
    public function getItems(): array {
        return $this->items;
    }
}
