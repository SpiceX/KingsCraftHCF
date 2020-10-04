<?php

namespace hcf\faction\command\subCommands;

use hcf\command\utils\SubCommand;
use hcf\faction\task\TeleportHomeTask;
use hcf\HCFPlayer;
use hcf\translation\Translation;
use hcf\translation\TranslationException;
use pocketmine\command\CommandSender;

class StuckSubCommand extends SubCommand {

    /**
     * StuckSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("stuck", "/faction stuck");
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
        if($sender->getFaction() === null) {
            $sender->sendMessage(Translation::getMessage("beInFaction"));
            return;
        }
        if($sender->getFaction()->getHome() === null) {
            $sender->sendMessage(Translation::getMessage("homeNotSet"));
            return;
        }
        if($sender->isTeleporting()) {
            return;
        }
        $sender->setTeleporting();
        $this->getCore()->getScheduler()->scheduleRepeatingTask(new TeleportHomeTask($sender, 30), 20);
    }
}