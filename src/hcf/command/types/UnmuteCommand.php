<?php

namespace hcf\command\types;

use hcf\command\utils\Command;
use hcf\HCFPlayer;
use hcf\translation\Translation;
use hcf\translation\TranslationException;
use PDO;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;

class UnmuteCommand extends Command
{

    /**
     * UnmuteCommand constructor.
     */
    public function __construct()
    {
        parent::__construct("unmute", "Unmute a player.", "/unmute <player>");
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
            || ($sender instanceof HCFPlayer && $sender->hasPermission("permission.admin")) || $sender->isOp()) {
            if (!isset($args[0])) {
                $sender->sendMessage(Translation::getMessage("usageMessage", [
                    "usage" => $this->getUsage()
                ]));
                return;
            }
            $stmt = $this->getCore()->getMySQLProvider()->getDatabase()->prepare("SELECT effector, reason, expiration FROM mutes WHERE username = :username;");
            $stmt->bindParam(":username", $args[0]);
            $stmt->execute();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $effector = $row['effector'];
                $reason = $row['reason'];
                $expiration = $row['expiration'];
                if ($effector === null && $reason === null && $expiration === null) {
                    $sender->sendMessage(Translation::getMessage("invalidPlayer"));
                    return;
                }
            }
            $stmt->closeCursor();

            $stmt = $this->getCore()->getMySQLProvider()->getDatabase()->prepare("DELETE FROM mutes WHERE username = :username;");
            $stmt->bindParam(":username", $args[0]);
            $stmt->execute();
            $stmt->closeCursor();
            $this->getCore()->getServer()->broadcastMessage(Translation::getMessage("punishmentRelivedBroadcast", [
                "name" => $args[0],
                "effector" => $sender->getName()
            ]));
            return;
        }
        $sender->sendMessage(Translation::getMessage("noPermission"));
    }
}