<?php

namespace hcf\wayPoint\command\subCommands;

use hcf\command\utils\SubCommand;
use hcf\HCFPlayer;
use hcf\translation\Translation;
use hcf\translation\TranslationException;
use pocketmine\command\CommandSender;

class DeleteSubCommand extends SubCommand {

    /**
     * DeleteSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("delete", "/waypoint delete <name>");
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
        if(!isset($args[1])) {
            $sender->sendMessage(Translation::getMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]));
            return;
        }
        $name = (string)$args[1];
        if($sender->getWayPoint($name) === null) {
            $sender->sendMessage(Translation::getMessage("nonExistingWayPoint", [
                "name" => $name
            ]));
            return;
        }
        $sender->sendMessage(Translation::getMessage("deleteWayPoint", [
            "name" => $name
        ]));
        $sender->removeWayPoint($name);
        return;
    }
}