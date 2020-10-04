<?php

namespace hcf\level\tile;

use hcf\HCF;
use hcf\level\inventory\BrewingInventory;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\InventoryHolder;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\network\mcpe\protocol\ContainerSetDataPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\tile\Container;
use pocketmine\tile\ContainerTrait;
use pocketmine\tile\Nameable;
use pocketmine\tile\NameableTrait;
use pocketmine\tile\Spawnable;
use InvalidArgumentException;

class BrewingStand extends Spawnable implements InventoryHolder, Container, Nameable {

    use NameableTrait, ContainerTrait;

    /** @var string */
    public const
        TAG_BREW_TIME = "BrewTime",
        TAG_FUEL = "Fuel",
        TAG_HAS_BOTTLE_0 = "has_bottle_0",
        TAG_HAS_BOTTLE_1 = "has_bottle_1",
        TAG_HAS_BOTTLE_2 = "has_bottle_2";

    /** @var string */
    private const TAG_HAS_BOTTLE_BASE = "has_bottle_"; // lazy

    /** @var int */
    public const
        MAX_BREW_TIME = 400,
        MAX_FUEL = 20;

    /** @var int[] */
    public const INGREDIENTS = [
        Item::NETHER_WART,
        Item::GLOWSTONE_DUST,
        Item::REDSTONE,
        Item::FERMENTED_SPIDER_EYE,
        Item::MAGMA_CREAM,
        Item::SUGAR,
        Item::GLISTERING_MELON,
        Item::SPIDER_EYE,
        Item::GHAST_TEAR,
        Item::BLAZE_POWDER,
        Item::GOLDEN_CARROT,
        Item::PUFFERFISH,
        Item::RABBIT_FOOT,
        Item::GUNPOWDER,
        Item::DRAGON_BREATH,
    ]; // used for hoppers...

    /** @var bool */
    public $brewing = false;

    /** @var CompoundTag */
    private $nbt;

    /** @var BrewingInventory */
    private $inventory = null;

    /**
     * BrewingStand constructor.
     *
     * @param Level $level
     * @param CompoundTag $nbt
     */
    public function __construct(Level $level, CompoundTag $nbt) {
        parent::__construct($level, $nbt);
        if($nbt->hasTag(self::TAG_BREW_TIME, ShortTag::class)) {
            $nbt->removeTag(self::TAG_BREW_TIME);
        }
        if($nbt->hasTag(self::TAG_FUEL, IntTag::class)) {
            $nbt->removeTag(self::TAG_FUEL);
        }
        if(!$nbt->hasTag(self::TAG_BREW_TIME, IntTag::class)) {
            $nbt->setInt(self::TAG_BREW_TIME, 0);
        }
        if(!$nbt->hasTag(self::TAG_FUEL, ByteTag::class)) {
            $nbt->setByte(self::TAG_FUEL, 0);
        }
        $this->inventory = new BrewingInventory($this);
        $this->loadItems($nbt);
        $this->scheduleUpdate();
    }

    /**
     * @return BrewingInventory|Inventory
     */
    public function getRealInventory() {
        return $this->inventory;
    }

    /**
     * @return string
     */
    public function getDefaultName(): string {
        return "Brewing Stand";
    }

    /**
     * @param CompoundTag $nbt
     */
    public function addAdditionalSpawnData(CompoundTag $nbt): void {
        $nbt->setShort(self::TAG_BREW_TIME, self::MAX_BREW_TIME);
    }

    /**
     * @param Item $item
     *
     * @return bool
     */
    public function isValidFuel(Item $item): bool {
        return ($item->getId() == Item::BLAZE_POWDER && $item->getDamage() == 0);
    }

    /**
     * @param Item $ingredient
     * @param Item $potion
     *
     * @return bool
     */
    public function isValidMatch(Item $ingredient, Item $potion): bool {
        $recipe = HCF::getInstance()->getLevelManager()->matchBrewingRecipe($ingredient, $potion);
        return $recipe !== null;
    }

    /**
     * @return bool
     */
    public function onUpdate(): bool {
        if($this->isClosed()) {
            return false;
        }
        $return = $consumeFuel = $canBrew = false;
        $this->timings->startTiming();
        $fuel = $this->getInventory()->getFuel();
        $ingredient = $this->getInventory()->getIngredient();
        for($i = 1; $i <= 3; $i++) {
            $hasBottle = false;
            $currItem = $this->inventory->getItem($i);
            if($this->isValidPotion($currItem)) {
                $canBrew = true;
                $hasBottle = true;
            }
            $this->setBottle($i - 1, $hasBottle);
        }
        if($this->getFuelValue() > 0) {
            $canBrew = true;
            $this->broadcastFuelAmount($this->getFuelValue());
            $this->broadcastFuelTotal(self::MAX_FUEL);
        }
        else {
            if(!$fuel->isNull()) {
                if($fuel->equals(Item::get(Item::BLAZE_POWDER, 0), true, false)) {
                    $consumeFuel = true;
                    $canBrew = true;
                }
            }
            else {
                $canBrew = false;
            }
        }
        if(!$ingredient->isNull() && $canBrew) {
            if($canBrew && $this->isValidIngredient($ingredient)) {
                foreach($this->inventory->getPotions() as $potion) {
                    $recipe = HCF::getInstance()->getLevelManager()->matchBrewingRecipe($ingredient, $potion);
                    if($recipe !== null) {
                        $canBrew = true;
                        break;
                    }
                    $canBrew = false;
                }
            }
        }
        else {
            $canBrew = false;
        }
        if($canBrew) {
            if($consumeFuel) {
                $fuel->count--;
                if($fuel->getCount() <= 0) {
                    $fuel = Item::get(Item::AIR);
                }
                $this->inventory->setFuel($fuel);
                $this->setFuelValue(self::MAX_FUEL);
                $this->broadcastFuelAmount(self::MAX_FUEL);
            }
            $return = true;
            $brewTime = $this->getBrewTime();
            $brewTime -= 4;
            $this->setBrewTime($brewTime);
            $this->brewing = true;
            $this->broadcastBrewTime($brewTime);
            $this->broadcastFuelTotal(self::MAX_FUEL);
            if($brewTime <= 0) {
                for($i = 1; $i <= 3; $i++) {
                    $hasBottle = false;
                    $potion = $this->inventory->getItem($i);
                    $recipe = HCF::getInstance()->getLevelManager()->matchBrewingRecipe($ingredient, $potion);
                    if($recipe != null and !$potion->isNull()) {
                        $this->inventory->setItem($i, $recipe->getResult());
                        $hasBottle = true;
                    }
                    $this->setBottle($i - 1, $hasBottle);
                }
                $this->getLevel()->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_POTION_BREWED);
                $ingredient->count--;
                if($ingredient->getCount() <= 0) {
                    $ingredient = Item::get(Item::AIR);
                }
                $this->inventory->setIngredient($ingredient);
                $this->saveItems($this->nbt);
                $fuelAmount = max($this->getFuelValue() - 1, 0);
                $this->setFuelValue($fuelAmount);
                $this->broadcastFuelAmount($fuelAmount);
                $this->brewing = false;
            }
        }
        else {
            $this->setBrewTime(self::MAX_BREW_TIME);
            $this->broadcastBrewTime(0);
            $this->brewing = false;
        }
        if($return) {
            $this->inventory->sendContents($this->inventory->getViewers());
            $this->onChanged();
        }
        $this->timings->stopTiming();
        return $return;
    }

    /**
     * @return BrewingInventory|Inventory
     */
    public function getInventory() {
        return $this->inventory;
    }

    /**
     * @param Item $item
     *
     * @return bool
     */
    public function isValidPotion(Item $item): bool {
        return (in_array($item->getId(), [Item::POTION, Item::SPLASH_POTION]));
    }

    /**
     * @param int $slot
     * @param bool $hasBottle
     */
    public function setBottle(int $slot, bool $hasBottle): void {
        if($slot > -1 && $slot < 3) {
            $this->getNBT()->setByte(self::TAG_HAS_BOTTLE_BASE . strval($slot), intval($hasBottle));
        }
        else {
            throw new InvalidArgumentException("Slot must be in the range of 0-2.");
        }
    }

    /**
     * @return CompoundTag
     */
    public function getNBT(): CompoundTag {
        return $this->nbt;
    }

    /**
     * @return int
     */
    public function getFuelValue(): int {
        return $this->getNBT()->getByte(self::TAG_FUEL, 0);
    }

    /**
     * @param int $value
     */
    public function broadcastFuelAmount(int $value): void {
        $pk = new ContainerSetDataPacket();
        $pk->property = ContainerSetDataPacket::PROPERTY_BREWING_STAND_FUEL_AMOUNT;
        $pk->value = $value;
        foreach($this->inventory->getViewers() as $viewer) {
            $pk->windowId = $viewer->getWindowId($this->getInventory());
            if($pk->windowId > 0) {
                $viewer->dataPacket($pk);
            }
        }
    }

    /**
     * @param int $value
     */
    public function broadcastFuelTotal(int $value): void {
        $pk = new ContainerSetDataPacket();
        $pk->property = ContainerSetDataPacket::PROPERTY_BREWING_STAND_FUEL_TOTAL;
        $pk->value = $value;
        foreach($this->inventory->getViewers() as $viewer) {
            $pk->windowId = $viewer->getWindowId($this->getInventory());
            if($pk->windowId > 0) {
                $viewer->dataPacket($pk);
            }
        }
    }

    /**
     * @param Item $item
     *
     * @return bool
     */
    public function isValidIngredient(Item $item): bool {
        return (in_array($item->getId(), self::INGREDIENTS) && $item->getDamage() == 0);
    }

    /**
     * @param int $fuel
     */
    public function setFuelValue(int $fuel): void {
        $this->getNBT()->setByte(self::TAG_FUEL, $fuel);
    }

    /**
     * @return int
     */
    public function getBrewTime(): int {
        return $this->getNBT()->getInt(self::TAG_BREW_TIME);
    }

    /**
     * @param int $time
     */
    public function setBrewTime(int $time): void {
        $this->getNBT()->setInt(self::TAG_BREW_TIME, $time);
    }

    /**
     * @param int $time
     */
    public function broadcastBrewTime(int $time): void {
        $pk = new ContainerSetDataPacket();
        $pk->property = ContainerSetDataPacket::PROPERTY_BREWING_STAND_BREW_TIME;
        $pk->value = $time;
        foreach($this->inventory->getViewers() as $viewer) {
            $pk->windowId = $viewer->getWindowId($this->getInventory());
            if($pk->windowId > 0) {
                $viewer->dataPacket($pk);
            }
        }
    }

    /**
     * @return CompoundTag
     */
    public function saveNBT(): CompoundTag {
        $this->saveItems($this->nbt);
        return parent::saveNBT();
    }

    public function loadBottles(): void {
        $this->loadItems($this->nbt);
    }

    /**
     * @param CompoundTag $nbt
     */
    protected function readSaveData(CompoundTag $nbt): void {
        $this->nbt = $nbt;
    }

    /**
     * @param CompoundTag $nbt
     */
    protected function writeSaveData(CompoundTag $nbt): void {
        $nbt->setShort(self::TAG_BREW_TIME, self::MAX_BREW_TIME);
    }
}