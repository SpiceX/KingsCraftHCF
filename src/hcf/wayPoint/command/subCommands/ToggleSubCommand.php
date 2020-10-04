<?php

namespace hcf\wayPoint\command\subCommands;

use hcf\command\utils\SubCommand;
use hcf\HCFPlayer;
use hcf\translation\Translation;
use hcf\translation\TranslationException;
use libs\utils\UtilsException;
use pocketmine\command\CommandSender;

class ToggleSubCommand extends SubCommand {

    /**
     * ToggleSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("toggle", "/waypoint toggle");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     * @throws UtilsException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if(!$sender instanceof HCFPlayer) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        if($sender->isShowingWayPoint() === true) {
            $sender->setShowWayPoint(false);
            $text = $sender->getFloatingText("WayPoint");
            if($text !== null) {
                $sender->removeFloatingText("WayPoint");
                return;
            }
        }
        else {
            $sender->setShowWayPoint(true);
        }
        $sender->sendMessage(Translation::getMessage("toggleWayPoint"));
        return;
    }
}