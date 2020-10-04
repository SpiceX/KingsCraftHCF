<?php

namespace hcf\faction\command\subCommands;

use hcf\command\utils\SubCommand;
use hcf\faction\Faction;
use hcf\HCFPlayer;
use hcf\translation\Translation;
use hcf\translation\TranslationException;
use pocketmine\command\CommandSender;

class SetHomeSubCommand extends SubCommand {

    /**
     * SetHomeSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("sethome", "/faction sethome");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        $manager = $this->getCore()->getFactionManager();
        if(!$sender instanceof HCFPlayer) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        if($sender->getFaction() === null) {
            $sender->sendMessage(Translation::getMessage("beInFaction"));
            return;
        }
        if($sender->getFactionRole() !== Faction::LEADER) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        $claim = $manager->getClaimInPosition($sender->asPosition());
        if($claim === null or $claim->getFaction()->getName() !== $sender->getFaction()->getName()) {
            $sender->sendMessage(Translation::getMessage("mustBeInClaim"));
            return;
        }
        $sender->getFaction()->setHome($sender->asPosition());
        $sender->sendMessage(Translation::getMessage("homeSet"));
    }
}