<?php

namespace hcf\faction\command\subCommands;

use hcf\command\utils\SubCommand;
use hcf\faction\Faction;
use hcf\HCFPlayer;
use hcf\translation\Translation;
use hcf\translation\TranslationException;
use pocketmine\command\CommandSender;

class UnclaimSubCommand extends SubCommand {

    /**
     * UnclaimSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("unclaim", "/faction unclaim ");
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
        if($sender->getFactionRole() !== Faction::LEADER) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        if(($claim = $this->getCore()->getFactionManager()->getClaimInPosition($sender->asPosition())) === null) {
            $sender->sendMessage(Translation::getMessage("notClaimed"));
            return;
        }
        if(!$claim->getFaction()->isInFaction($sender)) {
            $sender->sendMessage(Translation::getMessage("overrideClaim"));
            return;
        }
        $claim->getFaction()->removeClaim();
        $claim->getFaction()->setHome();
        $sender->sendMessage(Translation::getMessage("unclaimSuccess"));
    }
}