<?php

namespace hcf\command\modalForm;

use hcf\HCF;
use hcf\HCFPlayer;
use hcf\kit\task\SetClassTask;
use hcf\translation\Translation;
use hcf\translation\TranslationException;
use libs\form\MenuForm;
use libs\form\MenuOption;
use PDO;
use pocketmine\inventory\ArmorInventory;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class KitListForm extends MenuForm {

    /**
     * KitListForm constructor.
     *
     * @param HCFPlayer $player
     */
    public function __construct(HCFPlayer $player) {
        $title = TextFormat::BOLD . TextFormat::AQUA . "Kits";
        $text = "Select a kit. Any extra items will be dropped!";
        $kits = $player->getCore()->getKitManager()->getKits();
        $options = [];
        foreach($kits as $kit) {
            $options[] = new MenuOption($kit->getName());
        }
        parent::__construct($title, $text, $options);
    }

    /**
     * @param Player $player
     * @param int $selectedOption
     *
     * @throws TranslationException
     */
    public function onSubmit(Player $player, int $selectedOption): void {
        if(!$player instanceof HCFPlayer) {
            return;
        }
        $uuid = $player->getRawUniqueId();
        $name = $this->getOption($selectedOption)->getText();
        $time = time();
        $lowercaseName = strtolower($name);
        if(!$player->hasPermission("kit.$lowercaseName")) {
            $player->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        $kit = $player->getCore()->getKitManager()->getKitByName($name);
        $stmt = $player->getCore()->getMySQLProvider()->getDatabase()->prepare("SELECT $lowercaseName FROM kitCooldowns WHERE uuid = :uuid");
        $stmt->bindParam(":uuid", $uuid);
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            $cooldown = $row['cooldown'];
            $cooldown = $kit->getCooldown() - ($time - $cooldown);
            if($cooldown > 0) {
                $days = floor($cooldown / 86400);
                $hours = $hours = floor(($cooldown / 3600) % 24);
                $minutes = floor(($cooldown / 60) % 60);
                $seconds = $time % 60;
                $time = "$days days, $hours hours, $minutes minutes, $seconds seconds";
                $player->sendMessage(Translation::getMessage("kitCooldown", [
                    "time" => TextFormat::RED . $time
                ]));
                return;
            }
        }
        $stmt->closeCursor();
        
        $player->sendTitle(TextFormat::GREEN . TextFormat::BOLD . "Equipped", TextFormat::GRAY . $name . " Kit");
        foreach($kit->getItems() as $index => $item) {
            $id = $item->getId();
            if($id === Item::CHAIN_HELMET || $id === Item::GOLD_HELMET || $id === Item::IRON_HELMET || $id === Item::DIAMOND_HELMET || $id === Item::LEATHER_CAP) {
                if($player->getArmorInventory()->isSlotEmpty(ArmorInventory::SLOT_HEAD) === false) {
                    $player->getLevel()->dropItem($player, $item);
                    continue;
                }
                $player->getArmorInventory()->setHelmet($item);
                continue;
            }
            if($id === Item::CHAIN_CHESTPLATE || $id === Item::GOLD_CHESTPLATE || $id === Item::IRON_CHESTPLATE || $id === Item::DIAMOND_CHESTPLATE || $id === Item::LEATHER_CHESTPLATE) {
                if($player->getArmorInventory()->isSlotEmpty(ArmorInventory::SLOT_CHEST) === false) {
                    $player->getLevel()->dropItem($player, $item);
                    continue;
                }
                $player->getArmorInventory()->setChestplate($item);
                continue;
            }
            if($id === Item::CHAIN_LEGGINGS || $id === Item::GOLD_LEGGINGS || $id === Item::IRON_LEGGINGS || $id === Item::DIAMOND_LEGGINGS || $id === Item::LEATHER_LEGGINGS) {
                if($player->getArmorInventory()->isSlotEmpty(ArmorInventory::SLOT_LEGS) === false) {
                    $player->getLevel()->dropItem($player, $item);
                    continue;
                }
                $player->getArmorInventory()->setLeggings($item);
                continue;
            }
            if($id === Item::CHAIN_BOOTS || $id === Item::GOLD_BOOTS || $id === Item::IRON_BOOTS || $id === Item::DIAMOND_BOOTS || $id === Item::LEATHER_BOOTS) {
                if($player->getArmorInventory()->isSlotEmpty(ArmorInventory::SLOT_FEET) === false) {
                    $player->getLevel()->dropItem($player, $item);
                    continue;
                }
                $player->getArmorInventory()->setBoots($item);
                continue;
            }
            if($player->getInventory()->canAddItem($item)) {
                $player->getInventory()->addItem($item);
                continue;
            }
            $player->getLevel()->dropItem($player, $item);
        }
        $stmt = $player->getCore()->getMySQLProvider()->getDatabase()->prepare("UPDATE kitCooldowns SET $lowercaseName = :time WHERE uuid = :uuid");
        $stmt->bindParam(":time",$time);
        $stmt->bindParam(":uuid",$uuid);
        $stmt->execute();
        $stmt->closeCursor();
        HCF::getInstance()->getScheduler()->scheduleDelayedTask(new SetClassTask($player), 1);
    }
}