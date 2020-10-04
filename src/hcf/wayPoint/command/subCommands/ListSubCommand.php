<?php

namespace hcf\wayPoint\command\subCommands;

use hcf\command\utils\SubCommand;
use hcf\HCFPlayer;
use hcf\translation\Translation;
use hcf\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class ListSubCommand extends SubCommand {

    /**
     * ListSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("list", "/waypoint list");
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
        $sender->sendMessage(TextFormat::BOLD . TextFormat::GOLD . "WAY POINTS:");
        foreach($sender->getWayPoints() as $wayPoint) {
            $sender->sendMessage(TextFormat::WHITE . $wayPoint->getName());
        }
    }
}