<?php

namespace hcf\command\types;

use hcf\HCF;
use hcf\HCFPlayer;
use hcf\kit\Kit;
use hcf\kit\task\SetClassTask;
use hcf\translation\Translation;
use hcf\translation\TranslationException;
use muqsit\invmenu\inventory\InvMenuInventory;
use muqsit\invmenu\InvMenu;
use PDO;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class KitCommand extends Command
{

    /**
     * KitCommand constructor.
     */
    public function __construct()
    {
        parent::__construct("gkit", "Manage your kits", "/gkit");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$sender instanceof HCFPlayer) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        if (!isset($args[0])) {
            $menu = InvMenu::create(InvMenu::TYPE_CHEST);
            $menu->setName("§9Kings§fHCF §7Kits");
            $menu->readonly(true);
            $kits = HCF::getInstance()->getKitManager()->getKits();
            foreach ($kits as $kit) {
                for ($x = 0, $maxX = count($kits); $x <= $maxX; $x++) {
                    if (count($menu->getInventory()->getContents()) < $maxX){
                        if (rand(1, 3) == 1) {
                            $menu->getInventory()->setItem($x, Item::get($this->getRandomItem())->setCustomName($kit->getName()));
                        }
                    }
                }
            }
            $menu->setListener(
                function (Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action) use ($menu): bool {
                    $this->onSelectKit($player, $itemClicked->getName());
                    return false;
                });
            $menu->send($sender);
        } else {
            if (!$sender->isOp()) {
                $sender->sendMessage(Translation::getMessage("noPermission"));
                return;
            }
            switch ($args[0]) {
                case 'create':
                    if (!isset($args[1])) {
                        $sender->sendMessage("§cUsage: /gkit create <str: kitName>");
                        return;
                    }
                    if (
                        $sender->getInventoryState() === HCFPlayer::EMPTY_INVENTORY &&
                        $sender->getArmorInventoryState() === HCFPlayer::EMPTY_INVENTORY
                    ) {
                        $sender->sendMessage("§e[§cKitManager§e] §cYou cannot create kits from empty inventories.");
                        return;
                    }
                    HCF::getInstance()->getKitManager()->createFromInventory($args[1], $sender);
                    $lowercaseName = strtolower($args[1]);
                    HCF::getInstance()->getMySQLProvider()->getDatabase()->exec("ALTER TABLE kitCooldowns ADD COLUMN $lowercaseName INT DEFAULT 0;");
                    $sender->sendMessage("§2[§aKitManager§2] §aYou have created {$args[1]} kit.");
                    break;
                case 'delete':
                    if (!isset($args[1])) {
                        $sender->sendMessage("§cUsage: /gkit delete <str: kitName>");
                        return;
                    }
                    if (HCF::getInstance()->getKitManager()->getKitByName($args[1]) === null) {
                        $sender->sendMessage("§c> Kit does not exists!");
                        return;
                    }
                    $lowercaseName = strtolower($args[1]);
                    HCF::getInstance()->getKitManager()->removeKitByName($args[1]);
                    HCF::getInstance()->getMySQLProvider()->getDatabase()->exec("ALTER TABLE kitCooldowns DROP COLUMN $lowercaseName;");
                    $sender->sendMessage("§2[§aKitManager§2] §aYou have deleted {$args[1]} kit.");
            }
        }

        //$sender->sendForm(new KitListForm($sender));
    }

    /**
     * @param Player $player
     * @param string $selectedKit
     * @throws TranslationException
     * @noinspection NullPointerExceptionInspection
     */
    public function onSelectKit(Player $player, string $selectedKit): void
    {
        if (!$player instanceof HCFPlayer) {
            return;
        }
        $uuid = $player->getUniqueId()->toString();
        $time = time();
        $lowercaseName = strtolower($selectedKit);
        if (!$player->hasPermission("kit.$lowercaseName")) {
            $player->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        $kit = $player->getCore()->getKitManager()->getKitByName($selectedKit);
        $stmt = $player->getCore()->getMySQLProvider()->getDatabase()->prepare("SELECT $lowercaseName FROM kitCooldowns WHERE uuid = :uuid");
        if (!$stmt) {
            return;
        }
        $stmt->bindParam(":uuid", $uuid);
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            var_dump($row);
            $cooldown = $row[$lowercaseName];
            $cooldown = $kit->getCooldown() - ($time - $cooldown);
            if ($cooldown > 0) {
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
        $player->sendTitle(TextFormat::GREEN . TextFormat::BOLD . "Equipped", TextFormat::GRAY . $selectedKit . " Kit");
        foreach ($kit->getItems() as $index => $item) {
            $id = $item->getId();
            if ($id === Item::CHAIN_HELMET || $id === Item::GOLD_HELMET || $id === Item::IRON_HELMET || $id === Item::DIAMOND_HELMET || $id === Item::LEATHER_CAP) {
                if ($player->getArmorInventory()->isSlotEmpty(ArmorInventory::SLOT_HEAD) === false) {
                    $player->getLevel()->dropItem($player, $item);
                    continue;
                }
                $player->getArmorInventory()->setHelmet($item);
                continue;
            }
            if ($id === Item::CHAIN_CHESTPLATE || $id === Item::GOLD_CHESTPLATE || $id === Item::IRON_CHESTPLATE || $id === Item::DIAMOND_CHESTPLATE || $id === Item::LEATHER_CHESTPLATE) {
                if ($player->getArmorInventory()->isSlotEmpty(ArmorInventory::SLOT_CHEST) === false) {
                    $player->getLevel()->dropItem($player, $item);
                    continue;
                }
                $player->getArmorInventory()->setChestplate($item);
                continue;
            }
            if ($id === Item::CHAIN_LEGGINGS || $id === Item::GOLD_LEGGINGS || $id === Item::IRON_LEGGINGS || $id === Item::DIAMOND_LEGGINGS || $id === Item::LEATHER_LEGGINGS) {
                if ($player->getArmorInventory()->isSlotEmpty(ArmorInventory::SLOT_LEGS) === false) {
                    $player->getLevel()->dropItem($player, $item);
                    continue;
                }
                $player->getArmorInventory()->setLeggings($item);
                continue;
            }
            if ($id === Item::CHAIN_BOOTS || $id === Item::GOLD_BOOTS || $id === Item::IRON_BOOTS || $id === Item::DIAMOND_BOOTS || $id === Item::LEATHER_BOOTS) {
                if ($player->getArmorInventory()->isSlotEmpty(ArmorInventory::SLOT_FEET) === false) {
                    $player->getLevel()->dropItem($player, $item);
                    continue;
                }
                $player->getArmorInventory()->setBoots($item);
                continue;
            }
            if ($player->getInventory()->canAddItem($item)) {
                $player->getInventory()->addItem($item);
                continue;
            }
            $player->getLevel()->dropItem($player, $item);
        }
        $stmt = $player->getCore()->getMySQLProvider()->getDatabase()->prepare("UPDATE kitCooldowns SET $lowercaseName = :time WHERE uuid = :uuid");
        $stmt->bindParam(":time", $time);
        $stmt->bindParam(":uuid", $uuid);
        $stmt->execute();
        $stmt->closeCursor();
        HCF::getInstance()->getScheduler()->scheduleDelayedTask(new SetClassTask($player), 1);
    }

    private function getRandomItem()
    {
        $items = [
            Item::ENDER_EYE,
            Item::CHEST_MINECART,
            Item::WATERLILY,
            Item::CLOCK,
            Item::APPLE,
            Item::NETHER_STAR,
            Item::GLISTERING_MELON,
            Item::REDSTONE,
            Item::DIAMOND_PICKAXE,
            Item::TRIDENT,
            Item::ENDER_CHEST,
            Item::APPLE_ENCHANTED,
            Item::BEACON,
            Item::BEEF
        ];
        return $items[array_rand($items)];
    }
}