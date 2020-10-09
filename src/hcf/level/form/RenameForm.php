<?php

namespace hcf\level\form;

use libs\form\CustomForm;
use libs\form\CustomFormResponse;
use libs\form\element\Input;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class RenameForm extends CustomForm
{
    /**
     * RenameForm constructor.
     */
    public function __construct()
    {
        $title = TextFormat::BOLD . TextFormat::AQUA . "Rename";
        $options = [
            new Input("Name", "§7Name", "§7Your new item name")
        ];
        parent::__construct($title, $options);
    }

    /**
     * @param Player $player
     * @param CustomFormResponse $data
     */
    public function onSubmit(Player $player, CustomFormResponse $data): void
    {
        $item = $player->getInventory()->getItemInHand();
        $newName = $data->getString('Name');
        $item->setCustomName($newName);
        $player->getInventory()->setItemInHand($item);
        $player->sendMessage("§aItem renamed successfully.");
    }
}