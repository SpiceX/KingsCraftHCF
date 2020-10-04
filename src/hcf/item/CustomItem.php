<?php

namespace hcf\item;

use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\NamedTag;

class CustomItem extends Item {

    public const CUSTOM = "custom";

    /**
     * CustomItem constructor.
     *
     * @param int $id
     * @param string $customName
     * @param string[] $lore
     * @param EnchantmentInstance[] $enchants
     * @param NamedTag[] $tags
     * @param int $meta
     */
    public function __construct(int $id, string $customName, array $lore = [], array $enchants = [], array $tags = [], int $meta = 0) {
        $this->setCustomName($customName);
        $this->setLore($lore);
        foreach($enchants as $enchant) {
            $this->addEnchantment($enchant);
        }
        if(!empty($tags)) {
            $this->setNamedTagEntry(new CompoundTag(self::CUSTOM));
            /** @var CompoundTag $compoundTag */
            $compoundTag = $this->getNamedTagEntry(self::CUSTOM);
            foreach($tags as $tag) {
                $compoundTag->setTag($tag);
            }
        }
        parent::__construct($id, $meta);
    }

    /**
     * @return int
     */
    public function getMaxStackSize(): int {
        return 1;
    }

    /**
     * @return Item
     */
    public function getItemForm(): Item {
        $item = Item::get($this->id, $this->meta, $this->count);
        $item->setCustomName($this->getCustomName());
        $item->setLore($this->getLore());
        foreach($this->getEnchantments() as $enchantment) {
            $item->addEnchantment($enchantment);
        }
        /** @var CompoundTag $compoundTag */
        $compoundTag = $this->getNamedTagEntry(self::CUSTOM);
        if($compoundTag !== null) {
            $item->setNamedTagEntry($compoundTag);
        }
        return $item;
    }
}