<?php

namespace hcf\command\types;

use hcf\command\utils\Command;
use hcf\groups\GroupManager;
use hcf\HCFPlayer;
use hcf\translation\Translation;
use hcf\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class ListCommand extends Command {

    /**
     * ListCommand constructor.
     */
    public function __construct() {
        parent::__construct("list", "List current online players.", "/list");
    }


    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        Server::getInstance()->dispatchCommand($sender, "list");
    }
}