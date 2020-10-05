<?php


namespace hcf\command\types;


use hcf\command\utils\Command;
use hcf\HCFPlayer;
use hcf\item\AntiTrapper;
use hcf\item\EdibleNetherStar;
use hcf\item\Fireworks;
use hcf\item\InvisibilitySak;
use hcf\item\Live;
use hcf\item\LumberAxe;
use hcf\item\types\Crowbar;
use hcf\item\types\GrapplingHook;
use hcf\item\types\SwiftPearl;
use hcf\item\types\TeleportationBall;
use pocketmine\command\CommandSender;

class SpecialCommand extends Command
{

    public function __construct()
    {
        parent::__construct("special", "special command for items", "/special help", ['sp', 'special']);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if ($sender instanceof HCFPlayer) {
            if (!$sender->isOp()) {
                $sender->sendMessage("§cYou do not have permission to run this command!");
                return;
            }
            if (!isset($args[0], $args[1])) {
                $sender->sendMessage("§cUsage: /sp get <str: specialItem> <int: amount>");
                return;
            }
            if ($args[0] === 'get') {
                $item = null;
                switch ($args[1]) {
                    case 'bone':
                        $item = new AntiTrapper();
                        $item->setCount($args[2] ?? 1);
                        break;
                    case 'snowball':
                        $item = new TeleportationBall();
                        $item->setCount($args[2] ?? 1);
                        break;
                    case 'star':
                        $item = new EdibleNetherStar();
                        $item->setCount($args[2] ?? 1);
                        break;
                    case 'fireworks':
                        $item = new Fireworks();
                        $item->setCount($args[2] ?? 1);
                        break;
                    case 'grappling':
                        $item = new GrapplingHook();
                        $item->setCount($args[2] ?? 1);
                        break;
                    case 'crowbar':
                        $item = new Crowbar(5,5);
                        $item->setCount($args[2] ?? 1);
                        break;
                    case 'swift':
                        $item = new SwiftPearl();
                        $item->setCount($args[2] ?? 1);
                        break;
                    case 'sak':
                        $item = new InvisibilitySak();
                        $item->setCount($args[2] ?? 1);
                        break;
                    case 'live':
                        $item = new Live();
                        $item->setCount($args[2] ?? 1);
                        break;
                    case 'lumber':
                        $item = new LumberAxe();
                        $item->setCount($args[2] ?? 1);
                        break;
                    default:
                        $sender->sendMessage("§cInvalid special item!");
                        $sender->sendMessage("§eSpecial Items: lumber, live, sak, swift, crowbar, grappling, fireworks, star, snowball, bone");
                }
                if ($item !== null) {
                    $sender->getInventory()->addItem($item);
                    $sender->sendMessage("§aYou got {$item->getCount()} {$item->getCustomName()}.");
                }
            } else {
                $sender->sendMessage("§cUsage: /sp get <str: specialItem>");
                return;
            }
        }
    }
}