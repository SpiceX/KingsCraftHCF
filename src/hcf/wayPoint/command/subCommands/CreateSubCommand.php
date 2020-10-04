<?php

namespace hcf\wayPoint\command\subCommands;

use hcf\command\utils\SubCommand;
use hcf\HCFPlayer;
use hcf\translation\Translation;
use hcf\translation\TranslationException;
use hcf\wayPoint\WayPoint;
use pocketmine\command\CommandSender;

class CreateSubCommand extends SubCommand {

    /**
     * CreateSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("create", "/waypoint create <name>");
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
        if(count($sender->getWayPoints()) >= 5) {
            $sender->sendMessage(Translation::getMessage("maxWayPoints"));
            return;
        }
        if(!isset($args[1])) {
            $sender->sendMessage(Translation::getMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]));
            return;
        }
        $name = (string)$args[1];
        if(strlen($name) > 16) {
            $sender->sendMessage(Translation::getMessage("wayPointNameTooLong"));
            return;
        }
        if($sender->getWayPoint($name) !== null) {
            $sender->sendMessage(Translation::getMessage("existingWayPoint", [
                "name" => $name
            ]));
            return;
        }
        $sender->sendMessage(Translation::getMessage("createWayPoint", [
            "name" => $name
        ]));
        $x = $sender->getFloorX();
        $y = $sender->getFloorY();
        $z = $sender->getFloorZ();
        $level = $sender->getLevel();
        $waypoint = new WayPoint($name, $x, $y, $z, $level);
        $sender->addWayPoint($waypoint);
        return;
    }
}