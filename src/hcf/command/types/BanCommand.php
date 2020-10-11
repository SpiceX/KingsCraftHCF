<?php

namespace hcf\command\types;

use hcf\command\utils\Command;
use hcf\HCFPlayer;
use hcf\translation\Translation;
use hcf\translation\TranslationException;
use PDO;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;

class BanCommand extends Command
{

    /**
     * BanCommand constructor.
     */
    public function __construct()
    {
        parent::__construct("ban", "Ban a player.", "/ban <player> <reason> [days]");
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
        if ($sender instanceof ConsoleCommandSender
            || ($sender instanceof HCFPlayer && $sender->hasPermission("permission.mod")) || $sender->isOp()) {
            if (!isset($args[1])) {
                $sender->sendMessage(Translation::getMessage("usageMessage", [
                    "usage" => $this->getUsage()
                ]));
                return;
            }
            $expiration = null;
            if (isset($args[2]) && is_numeric($args[2])) {
                $punishTime = time();
                $expiration = $punishTime + ($args[2] * 86400);
            }
            $player = $this->getCore()->getServer()->getPlayerExact($args[0]);
            if ($player instanceof HCFPlayer) {
                $uuid = $player->getUniqueId()->toString();
                $name = $sender->getName();
                $stmt = $this->getCore()->getMySQLProvider()->getDatabase()->prepare("INSERT INTO bans(uuid, username, effector, reason, expiration) VALUES(:uuid, :username, :effector, :reason, :expiration);");
                $stmt->bindParam(":uuid", $uuid);
                $stmt->bindParam(":username", $args[0]);
                $stmt->bindParam(":effector", $name);
                $stmt->bindParam(":reason", $args[1]);
                $stmt->bindParam(":expiration", $expiration);
                $stmt->execute();
                $stmt->closeCursor();
                $time = "Permanent";
                if ($expiration !== null && isset($punishTime)) {
                    $time = $expiration - $punishTime;
                    $days = floor($time / 86400);
                    $hours = floor(($time / 3600) % 24);
                    $minutes = floor(($time / 60) % 60);
                    $seconds = $time % 60;
                    $time = "$days days, $hours hours, $minutes minutes, $seconds seconds";
                }
                $this->getCore()->getServer()->broadcastMessage(Translation::getMessage("banBroadcast", [
                    "name" => $player->getName(),
                    "effector" => $sender->getName(),
                    "time" => $time,
                    "reason" => $args[1]
                ]));
                $player->close(null, Translation::getMessage("banMessage", [
                    "name" => $sender->getName(),
                    "reason" => $args[1],
                    "time" => $time
                ]));
                return;
            }

            $name = $sender->getName();
            $stmt = $this->getCore()->getMySQLProvider()->getDatabase()->prepare("SELECT uuid FROM players WHERE username = :username");
            $stmt->bindParam(":username", $args[0]);
            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $uuid = $row['uuid'];
                if ($uuid !== null) {
                    $stmt = $this->getCore()->getMySQLProvider()->getDatabase()->prepare("INSERT INTO bans(uuid, username, effector, reason, expiration) VALUES(:uuid, :username, :effector, :reason, :expiration);");
                    $stmt->bindParam(":uuid", $uuid);
                    $stmt->bindParam(":username", $args[0]);
                    $stmt->bindParam(":effector", $name);
                    $stmt->bindParam(":reason", $args[1]);
                    $stmt->bindParam(":expiration", $expiration);
                    $stmt->execute();
                    $stmt->closeCursor();
                    $time = "Permanent";
                    if ($expiration !== null && isset($punishTime)) {
                        $time = $expiration - $punishTime;
                        $days = floor($time / 86400);
                        $hours = floor(($time / 3600) % 24);
                        $minutes = floor(($time / 60) % 60);
                        $seconds = $time % 60;
                        $time = "$days days, $hours hours, $minutes minutes, $seconds seconds";
                    }
                    $this->getCore()->getServer()->broadcastMessage(Translation::getMessage("banBroadcast", [
                        "name" => $args[0],
                        "effector" => $sender->getName(),
                        "time" => $time,
                        "reason" => $args[1]
                    ]));
                    return;
                }
            }
            $stmt->closeCursor();
            $sender->sendMessage(Translation::getMessage("invalidPlayer"));
            return;
        }
        $sender->sendMessage(Translation::getMessage("noPermission"));
    }
}