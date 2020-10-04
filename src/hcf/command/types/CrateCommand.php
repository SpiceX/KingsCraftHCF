<?php

namespace hcf\command\types;

use hcf\command\utils\Command;
use hcf\HCF;
use hcf\HCFPlayer;
use hcf\translation\Translation;
use hcf\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;

class CrateCommand extends Command
{

    public function __construct()
    {
        parent::__construct("crate", "crate command", "/crate help", ['crate']);
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if ($sender instanceof HCFPlayer) {
            if (!$sender->isOp()) {
                return;
            }
            if (!isset($args[0], $args[1])) {
                $sender->sendMessage("§cUsage: /crate set <str: crateName>");
                return;
            }
            if ($sender->getInventoryState() === HCFPlayer::EMPTY_INVENTORY) {
                $sender->sendMessage("§4[§cCRATES§4] §cYou cannot set crates from empty inventories.");
                return;
            }
            $crate = $this->getCore()->getCrateManager()->getCrate($args[1]);
            if($crate === null) {
                $sender->sendMessage(Translation::getMessage("invalidCrate"));
                return;
            }
            $items = [];
            foreach ($sender->getInventory()->getContents() as $item) {
                $items[] = $item;
            }
            $rewards = HCF::getInstance()->getCrateManager()->itemsToRewards($items);
            $crate->setRewards($rewards);
            for ($i = 0; $i <= $crate->getInventory()->getInventory()->getDefaultSize() - 1; $i++) {
                $reward = Item::get(Item::STAINED_GLASS);
                if (!empty($rewards)) {
                    $reward = array_shift($rewards)->getItem();
                }
                $crate->getInventory()->getInventory()->setItem($i, $reward);
            }
            $sender->sendMessage("§a[CRATES] Crate {$args[1]} configured!");
        }
    }
}