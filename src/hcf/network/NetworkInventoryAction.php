<?php

namespace hcf\network;

use hcf\level\inventory\AnvilInventory;
use hcf\level\inventory\EnchantInventory;
use hcf\network\packets\InventoryTransactionPacket;
use InvalidArgumentException;
use pocketmine\inventory\transaction\action\CreativeInventoryAction;
use pocketmine\inventory\transaction\action\DropItemAction;
use pocketmine\inventory\transaction\action\EnchantAction;
use pocketmine\inventory\transaction\action\InventoryAction;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\Player;
use UnexpectedValueException;

class NetworkInventoryAction {

    public const SOURCE_CONTAINER = 0;
    public const SOURCE_WORLD = 2;
    public const SOURCE_CREATIVE = 3;
    public const SOURCE_CRAFTING_GRID = 100;
    public const SOURCE_TODO = 99999;

    /**
     * Fake window IDs for the SOURCE_TODO type (99999)
     *
     * These identifiers are used for inventory source types which are not currently implemented server-side in MCPE.
     * As a general rule of thumb, anything that doesn't have a permanent inventory is client-side. These types are
     * to allow servers to track what is going on in client-side windows.
     *
     * Expect these to change in the future.
     */
    public const SOURCE_TYPE_CRAFTING_ADD_INGREDIENT = -2;
    public const SOURCE_TYPE_CRAFTING_REMOVE_INGREDIENT = -3;
    public const SOURCE_TYPE_CRAFTING_RESULT = -4;
    public const SOURCE_TYPE_CRAFTING_USE_INGREDIENT = -5;
    public const SOURCE_TYPE_ANVIL_INPUT = -10;
    public const SOURCE_TYPE_ANVIL_MATERIAL = -11;
    public const SOURCE_TYPE_ANVIL_RESULT = -12;
    public const SOURCE_TYPE_ANVIL_OUTPUT = -13;
    public const SOURCE_TYPE_ENCHANT_INPUT = -15;
    public const SOURCE_TYPE_ENCHANT_MATERIAL = -16;
    public const SOURCE_TYPE_ENCHANT_OUTPUT = -17;
    public const SOURCE_TYPE_TRADING_INPUT_1 = -20;
    public const SOURCE_TYPE_TRADING_INPUT_2 = -21;
    public const SOURCE_TYPE_TRADING_USE_INPUTS = -22;
    public const SOURCE_TYPE_TRADING_OUTPUT = -23;
    public const SOURCE_TYPE_BEACON = -24;
    /** Any client-side window dropping its contents when the player closes it */
    public const SOURCE_TYPE_CONTAINER_DROP_CONTENTS = -100;
    public const ACTION_MAGIC_SLOT_CREATIVE_DELETE_ITEM = 0;
    public const ACTION_MAGIC_SLOT_CREATIVE_CREATE_ITEM = 1;
    public const ACTION_MAGIC_SLOT_DROP_ITEM = 0;
    public const ACTION_MAGIC_SLOT_PICKUP_ITEM = 1;

    /** @var int */
    public $sourceType;

    /** @var int */
    public $windowId;

    /** @var int */
    public $sourceFlags = 0;

    /** @var int */
    public $inventorySlot;

    /** @var Item */
    public $oldItem;

    /** @var Item */
    public $newItem;

    /**
     * @param InventoryTransactionPacket $packet
     *
     * @return $this
     */
    public function read(InventoryTransactionPacket $packet): self
    {
        $this->sourceType = $packet->getUnsignedVarInt();
        switch($this->sourceType) {
            case self::SOURCE_CONTAINER:
                $this->windowId = $packet->getVarInt();
                break;
            case self::SOURCE_WORLD:
                $this->sourceFlags = $packet->getUnsignedVarInt();
                break;
            case self::SOURCE_CREATIVE:
                break;
            case self::SOURCE_CRAFTING_GRID:
            case self::SOURCE_TODO:
                $this->windowId = $packet->getVarInt();
                switch($this->windowId) {
                    /** @noinspection PhpMissingBreakStatementInspection */
                    case self::SOURCE_TYPE_CRAFTING_RESULT:
                        $packet->isFinalCraftingPart = true;
                    case self::SOURCE_TYPE_CRAFTING_USE_INGREDIENT:
                        $packet->isCraftingPart = true;
                        break;
                }
                break;
            default:
                throw new UnexpectedValueException("Unknown inventory action source type $this->sourceType");
        }
        $this->inventorySlot = $packet->getUnsignedVarInt();
        $this->oldItem = $packet->getSlot();
        $this->newItem = $packet->getSlot();
        return $this;
    }

    /**
     * @param InventoryTransactionPacket $packet
     */
    public function write(InventoryTransactionPacket $packet): void
    {
        $packet->putUnsignedVarInt($this->sourceType);
        switch($this->sourceType) {
            case self::SOURCE_TODO:
            case self::SOURCE_CRAFTING_GRID:
            case self::SOURCE_CONTAINER:
                $packet->putVarInt($this->windowId);
                break;
            case self::SOURCE_WORLD:
                $packet->putUnsignedVarInt($this->sourceFlags);
                break;
            case self::SOURCE_CREATIVE:
                break;
            default:
                throw new InvalidArgumentException("Unknown inventory action source type $this->sourceType");
        }
        $packet->putUnsignedVarInt($this->inventorySlot);
        $packet->putSlot($this->oldItem);
        $packet->putSlot($this->newItem);
    }

    /**
     * @param Player $player
     *
     * @return InventoryAction|null
     *
     * @throws UnexpectedValueException
     */
    public function createInventoryAction(Player $player) {
        switch($this->sourceType) {
            case self::SOURCE_CONTAINER:
                $window = $player->getWindow($this->windowId);
                if($window !== null) {
                    return new SlotChangeAction($window, $this->inventorySlot, $this->oldItem, $this->newItem);
                }
                throw new UnexpectedValueException("Player " . $player->getName() . " has no open container with window ID $this->windowId");
            case self::SOURCE_WORLD:
                if($this->inventorySlot !== self::ACTION_MAGIC_SLOT_DROP_ITEM) {
                    throw new UnexpectedValueException("Only expecting drop-item world actions from the client!");
                }
                return new DropItemAction($this->newItem);
            case self::SOURCE_CREATIVE:
                switch($this->inventorySlot) {
                    case self::ACTION_MAGIC_SLOT_CREATIVE_DELETE_ITEM:
                        $type = CreativeInventoryAction::TYPE_DELETE_ITEM;
                        break;
                    case self::ACTION_MAGIC_SLOT_CREATIVE_CREATE_ITEM:
                        $type = CreativeInventoryAction::TYPE_CREATE_ITEM;
                        break;
                    default:
                        throw new UnexpectedValueException("Unexpected creative action type $this->inventorySlot");
                }
                return new CreativeInventoryAction($this->oldItem, $this->newItem, $type);
            case self::SOURCE_CRAFTING_GRID:
            case self::SOURCE_TODO:
                //These types need special handling.
                switch($this->windowId) {
                    case self::SOURCE_TYPE_CRAFTING_ADD_INGREDIENT:
                    case self::SOURCE_TYPE_CRAFTING_REMOVE_INGREDIENT:
                    case self::SOURCE_TYPE_CONTAINER_DROP_CONTENTS: //TODO: this type applies to all fake windows, not just crafting
                        return new SlotChangeAction($player->getCraftingGrid(), $this->inventorySlot, $this->oldItem, $this->newItem);
                    case self::SOURCE_TYPE_CRAFTING_USE_INGREDIENT:
                    case self::SOURCE_TYPE_CRAFTING_RESULT:
                        return null;
                    case self::SOURCE_TYPE_ENCHANT_INPUT:
                    case self::SOURCE_TYPE_ENCHANT_MATERIAL:
                    case self::SOURCE_TYPE_ENCHANT_OUTPUT:
                        $inv = $player->getWindow(WindowIds::ENCHANT);
                        if(!($inv instanceof EnchantInventory)) {
                            return null;
                        }
                        new action\EnchantAction($inv, $this->inventorySlot, $this->oldItem, $this->newItem);
                        switch($this->windowId) {
                            case self::SOURCE_TYPE_ENCHANT_INPUT:
                                $this->inventorySlot = 0;
                                $local = $inv->getItem(0);

                                if($local->equals($this->newItem, true, false)) {
                                    $enchantments = [
                                        Enchantment::FIRE_ASPECT,
                                        Enchantment::BANE_OF_ARTHROPODS,
                                        Enchantment::SMITE,
                                        Enchantment::KNOCKBACK,
                                        Enchantment::THORNS,
                                        Enchantment::AQUA_AFFINITY,
                                        Enchantment::RESPIRATION,
                                        Enchantment::VANISHING,
                                        Enchantment::MENDING,
                                        Enchantment::FROST_WALKER,
                                        Enchantment::DEPTH_STRIDER,
                                        Enchantment::LUCK_OF_THE_SEA,
                                        Enchantment::LURE,
                                        Enchantment::BINDING,
                                        Enchantment::IMPALING,
                                        Enchantment::RIPTIDE,
                                        Enchantment::LOYALTY,
                                        Enchantment::CHANNELING
                                    ];
                                    foreach($enchantments as $enchantment) {
                                        if($this->newItem->hasEnchantment($enchantment)) {
                                            $this->newItem->removeEnchantment($enchantment);
                                        }
                                    }
                                    foreach($this->newItem->getEnchantments() as $enchantment) {
                                        if($enchantment->getLevel() > $enchantment->getType()->getMaxLevel()) {
                                            $enchantment = $enchantment->setLevel($enchantment->getType()->getMaxLevel());
                                            $this->newItem->addEnchantment($enchantment);
                                        }
                                    }
                                    if($this->newItem->hasEnchantments() && count($this->newItem->getEnchantments()) <= 0) {
                                        $this->newItem->removeEnchantments();
                                    }
                                    $inv->setItem(0, $this->newItem);
                                }
                                break;
                            case self::SOURCE_TYPE_ENCHANT_MATERIAL:
                                $this->inventorySlot = 1;
                                $inv->setItem(1, $this->oldItem);
                                break;
                            case self::SOURCE_TYPE_ENCHANT_OUTPUT:
                                break;
                        }
                        return new SlotChangeAction($inv, $this->inventorySlot, $this->oldItem, $this->newItem);
                    case self::SOURCE_TYPE_BEACON:
                        $inv = $player->getWindow(WindowIds::BEACON);
                        if(!($inv instanceof EnchantInventory)) {
                            return null;
                        }
                        $this->inventorySlot = 0;
                        return new SlotChangeAction($inv, $this->inventorySlot, $this->oldItem, $this->newItem);
                    case self::SOURCE_TYPE_ANVIL_INPUT:
                    case self::SOURCE_TYPE_ANVIL_MATERIAL:
                    case self::SOURCE_TYPE_ANVIL_RESULT:
                    case self::SOURCE_TYPE_ANVIL_OUTPUT:
                        $inv = $player->getWindow(WindowIds::ANVIL);
                        if(!($inv instanceof AnvilInventory)) {
                            return null;
                        }
                        switch($this->windowId) {
                            case self::SOURCE_TYPE_ANVIL_INPUT:
                                $this->inventorySlot = 0;
                                break;
                            case self::SOURCE_TYPE_ANVIL_MATERIAL:
                                $this->inventorySlot = 1;
                                break;
                            case self::SOURCE_TYPE_ANVIL_OUTPUT:
                                $inv->sendSlot(2, $inv->getViewers());
                                break;
                            case self::SOURCE_TYPE_ANVIL_RESULT:
                                $this->inventorySlot = 2;
                                $cost = $inv->getItem(2)->getNamedTag()->getInt("RepairCost", 1);
                                if($player->isSurvival() && $player->getXpLevel() < $cost) {
                                    return null;
                                }
                                $inv->clear(0);
                                if(!($material = $inv->getItem(1))->isNull()) {
                                    $material = clone $material;
                                    --$material->count;
                                    $inv->setItem(1, $material);
                                }
                                $enchantments = [
                                    Enchantment::FIRE_ASPECT,
                                    Enchantment::BANE_OF_ARTHROPODS,
                                    Enchantment::SMITE,
                                    Enchantment::KNOCKBACK,
                                    Enchantment::THORNS,
                                    Enchantment::AQUA_AFFINITY,
                                    Enchantment::RESPIRATION,
                                    Enchantment::VANISHING,
                                    Enchantment::MENDING,
                                    Enchantment::FROST_WALKER,
                                    Enchantment::DEPTH_STRIDER,
                                    Enchantment::LUCK_OF_THE_SEA,
                                    Enchantment::LURE,
                                    Enchantment::BINDING,
                                    Enchantment::IMPALING,
                                    Enchantment::RIPTIDE,
                                    Enchantment::LOYALTY,
                                    Enchantment::CHANNELING
                                ];
                                foreach($enchantments as $enchantment) {
                                    if($this->oldItem->hasEnchantment($enchantment)) {
                                        $this->oldItem->removeEnchantment($enchantment);
                                    }
                                }
                                foreach($this->oldItem->getEnchantments() as $enchantment) {
                                    if($enchantment->getLevel() > $enchantment->getType()->getMaxLevel()) {
                                        $enchantment = $enchantment->setLevel($enchantment->getType()->getMaxLevel());
                                        $this->oldItem->addEnchantment($enchantment);
                                    }
                                }
                                if(count($this->oldItem->getEnchantments()) <= 0 and $this->newItem->hasEnchantments()) {
                                    $this->oldItem->removeEnchantments();
                                }
                                $inv->setItem(2, $this->oldItem, false);
                                if($player->isSurvival()) {
                                    $player->subtractXpLevels($cost);
                                }
                        }
                        return new SlotChangeAction($inv, $this->inventorySlot, $this->oldItem, $this->newItem);
                }
                //TODO: more stuff
                throw new UnexpectedValueException("Player " . $player->getName() . " has no open container with window ID $this->windowId");
            default:
                throw new UnexpectedValueException("Unknown inventory source type $this->sourceType");
        }
    }
}