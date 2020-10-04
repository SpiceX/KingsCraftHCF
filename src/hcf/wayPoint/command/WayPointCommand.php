<?php

namespace hcf\wayPoint\command;

use hcf\command\utils\Command;
use hcf\translation\Translation;
use hcf\translation\TranslationException;
use hcf\wayPoint\command\subCommands\CreateSubCommand;
use hcf\wayPoint\command\subCommands\DeleteSubCommand;
use hcf\wayPoint\command\subCommands\ListSubCommand;
use hcf\wayPoint\command\subCommands\ToggleSubCommand;
use pocketmine\command\CommandSender;

class WayPointCommand extends Command {

    /**
     * WayPointCommand constructor.
     */
    public function __construct() {
        $this->addSubCommand(new CreateSubCommand());
        $this->addSubCommand(new DeleteSubCommand());
        $this->addSubCommand(new ListSubCommand());
        $this->addSubCommand(new ToggleSubCommand());
        parent::__construct("waypoint", "Manage way points.", "/waypoint <toggle/create/delete/list>", ["wp"]);
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
            $subCommand = $this->getSubCommand($args[0]);
            if($subCommand !== null) {
                $subCommand->execute($sender, $commandLabel, $args);
                return;
            }
            $sender->sendMessage(Translation::getMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]));
            return;
        }
        $sender->sendMessage(Translation::getMessage("usageMessage", [
            "usage" => $this->getUsage()
        ]));
        return;
    }
}