<?php

namespace hcf\kit\types;

use hcf\HCF;
use hcf\kit\Kit;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Item;

class CustomKit extends Kit
{
    /** @var Item[] */
    private $items;
    /** @var int */
    private $cooldown = 259200;

    /**
     * CustomKit constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        parent::__construct($name, 259200);
    }

    /**
     * @return Item[]
     */
    public function getItems(): array {
        return $this->items;
    }

    /**
     * @param Item $item
     */
    public function addItem(Item $item): void
    {
        $this->items[] = $item;
    }

    /**
     * @param PlayerInventory $inventory
     */
    public function addFromInventory(PlayerInventory $inventory): void
    {
        foreach ($inventory->getContents() as $content) {
            $this->items[] = $content;
        }
    }

    public function save(): void
    {
        $encoded = HCF::encodeItemList($this->items);
        $kitPath = $this->getPlugin()->getDataFolder() . 'kits' . DIRECTORY_SEPARATOR . "{$this->getName()}.ekt";
        $kitFile = @fopen($kitPath, 'wb') or die("Unable to open kit file!");
        @fwrite($kitFile, $encoded);
        @fclose($kitFile);
    }

    /**
     * @return int
     */
    public function getCooldown(): int
    {
        return $this->cooldown;
    }


}