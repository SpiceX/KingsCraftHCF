<?php

namespace hcf\command\types;

use hcf\command\utils\Command;
use hcf\translation\Translation;
use hcf\translation\TranslationException;
use PDO;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class AliasCommand extends Command {

    /**
     * AliasCommand constructor.
     */
    public function __construct() {
        parent::__construct("alias", "Check for alts.", "/alias <player>");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if(isset($args[0])) {
            if(!$sender->isOp() && !$sender->hasPermission("permission.staff")) {
                $sender->sendMessage(Translation::getMessage("noPermission"));
                return;
            }
            $name = $args[0];
            $stmt = $this->getCore()->getMySQLProvider()->getDatabase()->prepare("SELECT ipAddress FROM ipAddress WHERE username = :name");
            $stmt->bindParam(":name", $name);
            $stmt->execute();
            $addresses = [];
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if($row === false) {
                    $sender->sendMessage(Translation::getMessage("invalidPlayer"));
                }
                $addresses[] = $row['ipAddress'];
            }
            $stmt->closeCursor();
            $players = [];
            foreach($addresses as $address) {
                $stmt = $this->getCore()->getMySQLProvider()->getDatabase()->prepare("SELECT username FROM ipAddress WHERE ipAddress = :address");
                $stmt->bindParam(":address", $address);
                $stmt->execute();
                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $players[] = $row['ipAddress'];
                }
                $stmt->closeCursor();
            }
            $sender->sendMessage(TextFormat::DARK_RED . TextFormat::BOLD . strtoupper($name) . " IS ALSO KNOWN AS:");
            $sender->sendMessage(TextFormat::WHITE . implode(", ", $players));
            return;
        }
        $sender->sendMessage(Translation::getMessage("usageMessage", [
            "usage" => $this->getUsage()
        ]));
    }
}