<?php


namespace hcf\enchant\task;


use hcf\enchant\CustomEnchant;
use hcf\enchant\TickingEnchantment;
use hcf\HCF;
use hcf\util\Utils;
use pocketmine\item\Item;
use pocketmine\nbt\tag\IntTag;
use pocketmine\scheduler\Task;

class TickEnchantmentsTask extends Task
{
    /** @var HCF */
    private $plugin;

    public function __construct(HCF $plugin)
    {
        $this->plugin = $plugin;
    }

    public function onRun(int $currentTick): void
    {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
            $successfulEnchantments = [];
            foreach ($player->getInventory()->getContents() as $slot => $content) {
                if ($content->getNamedTagEntry("KingsCustomEnchantsVersion") === null && count($content->getEnchantments()) > 0) {
                    $player->getInventory()->setItem($slot, $this->cleanOldItems($content));
                }
                foreach ($content->getEnchantments() as $enchantmentInstance) {
                    /** @var TickingEnchantment $enchantment */
                    $enchantment = $enchantmentInstance->getType();
                    if ($enchantment instanceof CustomEnchant && $enchantment->canTick()) {
                        if (!in_array($enchantment, $successfulEnchantments) || $enchantment->supportsMultipleItems()) {
                            if ((
                                $enchantment->getUsageType() === CustomEnchant::TYPE_ANY_INVENTORY ||
                                $enchantment->getUsageType() === CustomEnchant::TYPE_INVENTORY ||
                                ($enchantment->getUsageType() === CustomEnchant::TYPE_HAND && $slot === $player->getInventory()->getHeldItemIndex())
                            )) {
                                if ($currentTick % $enchantment->getTickingInterval() === 0) {
                                    $enchantment->onTick($player, $content, $player->getInventory(), $slot, $enchantmentInstance->getLevel());
                                    $successfulEnchantments[] = $enchantment;
                                }
                            }
                        }
                    }
                }
            }
            foreach ($player->getArmorInventory()->getContents() as $slot => $content) {
                if ($content->getNamedTagEntry("KingsCustomEnchantsVersion") === null && count($content->getEnchantments()) > 0) {
                    $player->getArmorInventory()->setItem($slot, $this->cleanOldItems($content));
                }
                foreach ($content->getEnchantments() as $enchantmentInstance) {
                    /** @var TickingEnchantment $enchantment */
                    $enchantment = $enchantmentInstance->getType();
                    if ($enchantment instanceof CustomEnchant && $enchantment->canTick()) {
                        if (!in_array($enchantment, $successfulEnchantments) || $enchantment->supportsMultipleItems()) {
                            if ((
                                $enchantment->getUsageType() === CustomEnchant::TYPE_ANY_INVENTORY ||
                                $enchantment->getUsageType() === CustomEnchant::TYPE_ARMOR_INVENTORY ||
                                $enchantment->getUsageType() === CustomEnchant::TYPE_HELMET && Utils::isHelmet($content) ||
                                $enchantment->getUsageType() === CustomEnchant::TYPE_CHESTPLATE && Utils::isChestplate($content) ||
                                $enchantment->getUsageType() === CustomEnchant::TYPE_LEGGINGS && Utils::isLeggings($content) ||
                                $enchantment->getUsageType() === CustomEnchant::TYPE_BOOTS && Utils::isBoots($content)
                            )) {
                                if ($currentTick % $enchantment->getTickingInterval() === 0) {
                                    $enchantment->onTick($player, $content, $player->getArmorInventory(), $slot, $enchantmentInstance->getLevel());
                                    $successfulEnchantments[] = $enchantment;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function cleanOldItems(Item $item): Item
    {
        foreach ($item->getEnchantments() as $enchantmentInstance) {
            $enchantment = $enchantmentInstance->getType();
            if ($enchantment instanceof CustomEnchant) {
                $item->setCustomName(str_replace("\n" . Utils::getColorFromRarity($enchantment->getRarity()) . $enchantment->getName() . " " . Utils::getRomanNumeral($enchantmentInstance->getLevel()), "", $item->getCustomName()));
                $lore = $item->getLore();
                if (($key = array_search(Utils::getColorFromRarity($enchantment->getRarity()) . $enchantment->getName() . " " . Utils::getRomanNumeral($enchantmentInstance->getLevel()), $lore))) {
                    unset($lore[$key]);
                }
                $item->setLore($lore);
            }
        }
        $item->setNamedTagEntry(new IntTag("KingsCustomEnchantsVersion", 0));
        return $item;
    }
}