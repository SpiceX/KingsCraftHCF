<?php

namespace hcf\faction\command\subCommands;

use hcf\command\utils\SubCommand;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class TopSubCommand extends SubCommand {

    /**
     * TopSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("top", "/faction top");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        $stmt = $this->getCore()->getMySQLProvider()->getDatabase()->prepare("SELECT name, balance FROM factions ORDER BY balance DESC LIMIT 10");
        $stmt->execute();
        $stmt->bind_result($name, $balance);
        $place = 1;
        $text = $text = TextFormat::DARK_AQUA . TextFormat::BOLD . "TOP 10 RICHEST FACTIONS";
        while($stmt->fetch()) {
            $text .= "\n" . TextFormat::BOLD . TextFormat::AQUA . "$place. " . TextFormat::RESET . TextFormat::DARK_GREEN . $name . TextFormat::DARK_GRAY . " | " . TextFormat::GREEN . "$" . $balance;
            $place++;
        }
        $stmt->close();
        $sender->sendMessage($text);
    }
}