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
            if (!isset($args[0])) {
                $sender->sendMessage("§cUsage:");
                $sender->sendMessage("§c/crate set <str: crateName>");
                $sender->sendMessage("§c/crate portatil");
                return;
            }
            if ($sender->getInventoryState() === HCFPlayer::EMPTY_INVENTORY) {
                $sender->sendMessage("§4[§cCRATES§4] §cYou cannot set crates from empty inventories.");
                return;
            }
            switch ($args[0]) {
                case 'help':
                    $sender->sendMessage("§a[CRATES] Crates help page §e(1/1)\n" .
                    "§a/crate set <str: crateName>\n" .
                    "§a/crate portable set <str: crateName> <bool: saveCrate>\n" .
                    "§a/crate portable get <str: crateName>\n" .
                    "§a/crate portable list\n"
                    );
                    break;
                case 'set':
                    if (!isset($args[1])) {
                        $sender->sendMessage("§cUsage:");
                        $sender->sendMessage("§c/crate set <str: crateName>");
                        return;
                    }
                    $crate = $this->getCore()->getCrateManager()->getCrate($args[1]);
                    if ($crate === null) {
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
                    break;
                case 'portatil':
                    if (!isset($args[1])) {
                        $sender->sendMessage("§cUsage:");
                        $sender->sendMessage("§c/crate portatil set <str: crateName> <bool: saveCrate>");
                        $sender->sendMessage("§c/crate portatil get <str: crateName>");
                        $sender->sendMessage("§c/crate portatil list");
                        return;
                    }
                    if ($args[1] === 'set') {
                        if (isset($args[3])) {
                            $crate = $this->getCore()->getCrateManager()->createPortatilCrate($sender, $args[2], (bool)$args[2]);
                        } else {
                            $crate = $this->getCore()->getCrateManager()->createPortatilCrate($sender, $args[2]);
                        }
                        $crate->spawnTo($sender);
                        $sender->sendMessage("§a[CRATES] Portatil Crate {$args[2]} created!");
                    }
                    if ($args[1] === 'get') {
                        $crate = $this->getCore()->getCrateManager()->getPortatilCrate($args[2]);
                        if ($crate !== null) {
                            $crate->spawnTo($sender);
                            $sender->sendMessage("§a[CRATES] Portatil Crate {$args[2]} obtained!");
                            return;
                        }
                        $sender->sendMessage("§a[CRATES] Portatil Crate {$args[2]} not found!");
                    }
                    if ($args[1] === 'list') {
                        $list = "§7> Portatil Crates:\n";
                        foreach ($this->getCore()->getCrateManager()->getPortatilCrates() as $name => $crate) {
                            $list .= "§7- $name\n";
                        }
                        $sender->sendMessage($list);
                    }
                    break;
                default:
                    $sender->sendMessage("§cUsage: /crate help");
            }

        }
    }
}