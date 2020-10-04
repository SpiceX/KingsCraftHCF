<?php


namespace hcf\command\types;


use hcf\command\utils\Command;
use hcf\item\LumberAxe;
use pocketmine\command\CommandSender;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\Player;

class TestCommand extends  Command
{

    public function __construct()
    {
        parent::__construct("test", "", "", ['test']);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$sender instanceof Player){
            return;
        }
        switch ($args[0]){
            case 'armor':
                $sender->getArmorInventory()->setHelmet(Item::get(Item::DIAMOND_HELMET));
                break;
            case 'axe':
                $item = new LumberAxe();
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::BANE_OF_ARTHROPODS)));
                $sender->getInventory()->addItem($item);
                break;
        }
    }
}