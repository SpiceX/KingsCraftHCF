<?php


namespace hcf\util;


use hcf\enchant\CustomEnchant;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;

class Utils
{
    public const THIN_TAG = TextFormat::ESCAPE . "　";

    const TYPE_NAMES = [
        CustomEnchant::ITEM_TYPE_ARMOR => "Armor",
        CustomEnchant::ITEM_TYPE_HELMET => "Helmet",
        CustomEnchant::ITEM_TYPE_CHESTPLATE => "Chestplate",
        CustomEnchant::ITEM_TYPE_LEGGINGS => "Leggings",
        CustomEnchant::ITEM_TYPE_BOOTS => "Boots",
        CustomEnchant::ITEM_TYPE_WEAPON => "Weapon",
        CustomEnchant::ITEM_TYPE_SWORD => "Sword",
        CustomEnchant::ITEM_TYPE_BOW => "Bow",
        CustomEnchant::ITEM_TYPE_TOOLS => "Tools",
        CustomEnchant::ITEM_TYPE_PICKAXE => "Pickaxe",
        CustomEnchant::ITEM_TYPE_AXE => "Axe",
        CustomEnchant::ITEM_TYPE_SHOVEL => "Shovel",
        CustomEnchant::ITEM_TYPE_HOE => "Hoe",
        CustomEnchant::ITEM_TYPE_DAMAGEABLE => "Damageable",
        CustomEnchant::ITEM_TYPE_GLOBAL => "Global",
        CustomEnchant::ITEM_TYPE_COMPASS => "Compass",
    ];
    const RARITY_NAMES = [
        CustomEnchant::RARITY_COMMON => "Common",
        CustomEnchant::RARITY_UNCOMMON => "Uncommon",
        CustomEnchant::RARITY_RARE => "Rare",
        CustomEnchant::RARITY_MYTHIC => "Mythic"
    ];

    public static function getColorFromRarity(int $rarity): string
    {
        $rarityColors = [
            'common' => 'yellow',
            'uncommon' => 'blue',
            'rare' => 'gold',
            'mythic' => 'light_purple',
        ];
        return self::getTFConstFromString($rarityColors[strtolower(self::RARITY_NAMES[$rarity])]);
    }

    public static function getTFConstFromString(string $color): string
    {
        $colorConversionTable = [
            "BLACK" => TextFormat::BLACK,
            "DARK_BLUE" => TextFormat::DARK_BLUE,
            "DARK_GREEN" => TextFormat::DARK_GREEN,
            "DARK_AQUA" => TextFormat::DARK_AQUA,
            "DARK_RED" => TextFormat::DARK_RED,
            "DARK_PURPLE" => TextFormat::DARK_PURPLE,
            "GOLD" => TextFormat::GOLD,
            "GRAY" => TextFormat::GRAY,
            "DARK_GRAY" => TextFormat::DARK_GRAY,
            "BLUE" => TextFormat::BLUE,
            "GREEN" => TextFormat::GREEN,
            "AQUA" => TextFormat::AQUA,
            "RED" => TextFormat::RED,
            "LIGHT_PURPLE" => TextFormat::LIGHT_PURPLE,
            "YELLOW" => TextFormat::YELLOW,
            "WHITE" => TextFormat::WHITE
        ];
        return $colorConversionTable[strtoupper($color)] ?? TextFormat::GRAY;
    }

    public static function isHelmet(Item $item): bool
    {
        return in_array($item->getId(), [Item::LEATHER_CAP, Item::CHAIN_HELMET, Item::IRON_HELMET, Item::GOLD_HELMET, Item::DIAMOND_HELMET]);
    }

    public static function isChestplate(Item $item): bool
    {
        return in_array($item->getId(), [Item::LEATHER_TUNIC, Item::CHAIN_CHESTPLATE, Item::IRON_CHESTPLATE, Item::GOLD_CHESTPLATE, Item::DIAMOND_CHESTPLATE, Item::ELYTRA]);
    }

    public static function isLeggings(Item $item): bool
    {
        return in_array($item->getId(), [Item::LEATHER_PANTS, Item::CHAIN_LEGGINGS, Item::IRON_LEGGINGS, Item::GOLD_LEGGINGS, Item::DIAMOND_LEGGINGS]);
    }

    public static function isBoots(Item $item): bool
    {
        return in_array($item->getId(), [Item::LEATHER_BOOTS, Item::CHAIN_BOOTS, Item::IRON_BOOTS, Item::GOLD_BOOTS, Item::DIAMOND_BOOTS]);
    }

    public static function toThin(string $string): string
    {
        return preg_replace("/%*(([a-z0-9_]+\.)+[a-z0-9_]+)/i", "%$1", $string) . self::THIN_TAG;
    }

    public static function getRandomVector(): Vector3
    {
        $x = mt_rand() / mt_getrandmax() * 2 - 1;
        $y = mt_rand() / mt_getrandmax() * 2 - 1;
        $z = mt_rand() / mt_getrandmax() * 2 - 1;
        $v = new Vector3($x, $y, $z);
        return $v->normalize();
    }

    public static function getRomanNumeral(int $integer): string
    {
        $romanNumeralConversionTable = [
            'M' => 1000,
            'CM' => 900,
            'D' => 500,
            'CD' => 400,
            'C' => 100,
            'XC' => 90,
            'L' => 50,
            'XL' => 40,
            'X' => 10,
            'IX' => 9,
            'V' => 5,
            'IV' => 4,
            'I' => 1
        ];
        $romanString = "";
        while ($integer > 0) {
            foreach ($romanNumeralConversionTable as $rom => $arb) {
                if ($integer >= $arb) {
                    $integer -= $arb;
                    $romanString .= $rom;
                    break;
                }
            }
        }
        return $romanString;
    }
}