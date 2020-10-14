<?php


namespace hcf\command\types;


use hcf\command\utils\Command;
use hcf\level\block\Anvil;
use hcf\translation\Translation;
use hcf\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\item\Durable;
use pocketmine\Player;

class RepairCommand extends Command
{
    /** @var array */
    private $cooldowns = [];

    /**
     * PVPCommand constructor.
     */
    public function __construct()
    {
        parent::__construct("repair", "Repair items.", "/repair <option: all>");
        $this->setPermission('cmd.repair');
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if ($sender instanceof Player) {
            if (isset($args[0]) && $args[0] === 'all') {
                if (!$sender->hasPermission("cmd.repairall")) {
                    $sender->sendMessage(Translation::getMessage("noPermission"));
                    return;
                }
                if (isset($this->cooldowns[$sender->getRawUniqueId()])){
                    $time = date('Y-m-d H:i:s', $this->cooldowns[$sender->getRawUniqueId()]);
                    $sender->sendMessage("§cThis action is on cooldown until $time");
                    return;
                }
                $this->cooldowns[$sender->getRawUniqueId()] = strtotime('2 hours');
                Anvil::repairInventory($sender);
                return;
            }
            $item = $sender->getInventory()->getItemInHand();
            if ($item instanceof Durable) {
                $item->setDamage(0);
            } else {
                $sender->sendMessage("§cThis item can not be repaired!");
                return;
            }
            $sender->getInventory()->setItemInHand($item);
            if ($item->hasCustomName()){
                $sender->sendMessage("§a{$item->getCustomName()} has been repaired.");
            } else {
                $sender->sendMessage("§a{$item->getName()} has been repaired.");
            }
        }
    }
}