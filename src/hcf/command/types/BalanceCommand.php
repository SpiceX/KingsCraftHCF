<?php

namespace hcf\command\types;

use hcf\command\utils\Command;
use hcf\HCFPlayer;
use hcf\translation\Translation;
use hcf\translation\TranslationException;
use PDO;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class BalanceCommand extends Command {

    /**
     * BalanceCommand constructor.
     */
    public function __construct() {
        parent::__construct("balance", "Show your or another player's balance.", "/balance [player]", ["bal"]);
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if(!$sender instanceof HCFPlayer) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        $name = "Your";
        $balance = $sender->getBalance();
        if(isset($args[0])) {
            $player = $this->getCore()->getServer()->getPlayer($args[0]);
            if(!$player instanceof HCFPlayer) {
                $stmt = $this->getCore()->getMySQLProvider()->getDatabase()->prepare("SELECT balance FROM players WHERE username = :name");
                $stmt->bindParam(":name", $args[0]);
                $stmt->execute();
                while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                    $balance = $row['balance'];
                    if($balance === false) {
                        $sender->sendMessage(Translation::getMessage("invalidPlayer"));
                        return;
                    }
                }
                $stmt->closeCursor();
                $name = "$args[0]'s";
            }
        }
        $sender->sendMessage(Translation::getMessage("balance", [
            "name" => TextFormat::GOLD . $name,
            "amount" => TextFormat::GREEN . "$" . $balance
        ]));
    }
}