<?php

namespace hcf\level;

use hcf\HCF;
use InvalidStateException;
use pocketmine\inventory\CraftingManager;
use pocketmine\inventory\Recipe;
use pocketmine\item\Item;
use pocketmine\utils\UUID;

class BrewingRecipe implements Recipe {

    /** @var null|UUID */
    private $id;

    /** @var Item */
    private $output;

    /** @var Item */
    private $ingredient;

    /** @var Item */
    private $potion;

    /**
     * BrewingRecipe constructor.
     *
     * @param Item $result
     * @param Item $ingredient
     * @param Item $potion
     */
    public function __construct(Item $result, Item $ingredient, Item $potion) {
        $this->output = clone $result;
        $this->ingredient = clone $ingredient;
        $this->potion = clone $potion;
    }

    /**
     * @return Item
     */
    public function getPotion(): Item
    {
        return clone $this->potion;
    }

    /**
     * @return UUID|null
     */
    public function getId(): ?UUID
    {
        return $this->id;
    }

    /**
     * @param UUID $id
     */
    public function setId(UUID $id): void
    {
        if($this->id !== null) {
            throw new InvalidStateException("ID is already set");
        }
        $this->id = $id;
    }

    /**
     * @param Item $item
     */
    public function setInput(Item $item): void
    {
        $this->ingredient = clone $item;
    }

    /**
     * @return Item
     */
    public function getInput(): Item
    {
        return clone $this->ingredient;
    }

    /**
     * @return Item
     */
    public function getResult(): Item
    {
        return clone $this->output;
    }

    /**
     * @param CraftingManager $manager
     */
    public function registerToCraftingManager(CraftingManager $manager): void {
        HCF::getInstance()->getLevelManager()->registerBrewingRecipe($this);
    }
}