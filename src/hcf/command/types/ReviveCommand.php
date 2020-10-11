<?php

namespace hcf\command\types;

use hcf\command\utils\Command;
use hcf\HCFPlayer;
use hcf\translation\Translation;
use hcf\translation\TranslationException;
use PDO;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class ReviveCommand extends Command {

    /**
     * ReviveCommand constructor.
     */
    public function __construct() {
        parent::__construct("revive", "Revive a fellow faction member.", "/revive <player>");
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
        if(!isset($args[0])) {
            $sender->sendMessage(Translation::getMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]));
            return;
        }
        if($sender->getFaction() === null) {
            $sender->sendMessage(Translation::getMessage("beInFaction"));
            return;
        }
        if($sender->getLives() <= 0) {
            $sender->sendMessage(Translation::getMessage("noLives"));
            return;
        }
        $player = $args[0];
        if($sender->getFaction()->isInFaction($player)) {
            $sender->sendMessage(Translation::getMessage("invalidPlayer"));
            return;
        }
        $stmt = $this->getCore()->getMySQLProvider()->getDatabase()->prepare("SELECT deathBanTime FROM players WHERE username = :username");
        $stmt->bindParam(":username", $player);
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            $deathBanTime = $row['deathBanTime'];
            if($deathBanTime === null) {
                $sender->sendMessage(Translation::getMessage("invalidPlayer"));
                return;
            }
        }
        $stmt->closeCursor();
        $sender->removeLife();
        $time = 0;
        $stmt = $this->getCore()->getMySQLProvider()->getDatabase()->prepare("UPDATE players SET deathBanTime = :time WHERE username = :username");
        $stmt->bindParam(":time",$time);
        $stmt->bindParam(":username",$player);
        $stmt->execute();
        $stmt->closeCursor();
        $sender->sendMessage(Translation::getMessage("reviveSuccess", [
            "name" => TextFormat::GOLD . $player
        ]));
        $sender->sendMessage(Translation::getMessage("lives", [
            "amount" => TextFormat::GREEN . $sender->getLives()
        ]));
    }
}