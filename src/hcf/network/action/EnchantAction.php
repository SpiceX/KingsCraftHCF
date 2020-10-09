<?php


namespace hcf\network\action;


use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\Player;

class EnchantAction extends SlotChangeAction{

    public function isValid(Player $source) : bool{
        return true; // client-side enchant so we need this
    }
}