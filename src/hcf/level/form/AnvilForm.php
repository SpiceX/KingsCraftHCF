<?php


namespace hcf\level\form;


use hcf\HCFPlayer;
use hcf\level\block\Anvil;
use hcf\translation\Translation;
use hcf\translation\TranslationException;
use libs\form\MenuForm;
use libs\form\MenuOption;
use pocketmine\item\Durable;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class AnvilForm extends MenuForm
{

    /**
     * AnvilForm constructor.
     *
     */
    public function __construct() {
        $title = TextFormat::BOLD . TextFormat::AQUA . "Anvil";
        $text = "Select item to repair, or repair all items in your inventory!";
        $options = [
            new MenuOption("§eRename Item"),
            new MenuOption("§eFix Item"),
            new MenuOption("§eRepair all in your inventory"),
        ];
        parent::__construct($title, $text, $options);
    }

    /**
     * @param Player $player
     * @param int $selectedOption
     * @throws TranslationException
     */
    public function onSubmit(Player $player, int $selectedOption): void
    {
        if (!$player instanceof HCFPlayer) {
            return;
        }
        switch ($selectedOption){
            case 0:
                if(!$player->hasPermission("anvil.rename")) {
                    $player->sendMessage(Translation::getMessage("noPermission"));
                    return;
                }
                $player->sendForm(new RenameForm());
                break;
            case 1:
                if(!$player->hasPermission("anvil.repair")) {
                    $player->sendMessage(Translation::getMessage("noPermission"));
                    return;
                }
                $item = $player->getInventory()->getItemInHand();
                if (!$item instanceof Durable){
                    $player->sendMessage("§cHey! That item can not be repaired.");
                    return;
                }
                $item->setDamage(0);
                $player->getInventory()->setItemInHand($item);
                $player->sendMessage("§a{$item->getName()} was repaired! ;)");
                break;
            case 2:
                if(!$player->hasPermission("anvil.repairall")) {
                    $player->sendMessage(Translation::getMessage("noPermission"));
                    return;
                }
                Anvil::repairInventory($player);
                break;
        }
    }

}