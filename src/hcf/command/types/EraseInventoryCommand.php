<?php

namespace hcf\command\types;

use hcf\command\utils\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\Server;

class EraseInventoryCommand extends Command
{

    /**
     * EraseInventoryCommand constructor.
     */
    public function __construct()
    {
        parent::__construct("eraseinventory", "eraseinventory command", "/eraseinventory <player>", ['eraseinventory']);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if ($sender instanceof ConsoleCommandSender) {
            if (!isset($args[0])) {
                $sender->sendMessage("§cUsage: /eraseinventory <player>");
                return;
            }
            $player = strtolower($args[0]);
            if (is_file(Server::getInstance()->getDataPath() . 'players' . DIRECTORY_SEPARATOR . "{$player}.dat")) {
                unlink(Server::getInstance()->getDataPath() . 'players' . DIRECTORY_SEPARATOR . "{$player}.dat");
                $sender->sendMessage("§c{$args[0]} Player inventory data has been erased.");
            } else {
                $sender->sendMessage("§c{$args[0]} player inventory data does not exists.");
            }
        }
    }
}