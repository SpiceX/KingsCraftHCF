<?php

namespace hcf\wayPoint\task;

use hcf\HCF;
use hcf\HCFPlayer;
use libs\utils\UtilsException;
use pocketmine\level\Position;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

class WayPointMoveTask extends Task {

    /**
     * @param int $currentTick
     *
     * @throws UtilsException
     */
    public function onRun(int $currentTick) {
        /** @var HCFPlayer $player */
        foreach(HCF::getInstance()->getServer()->getOnlinePlayers() as $player) {
            if((!$player->isOnline()) or ($player->getLevel() === null) or $player->isShowingWayPoint() === false) {
                return;
            }
            $message = [];
            foreach($player->getWayPoints() as $wayPoint) {
                if($wayPoint->getLevel()->getName() !== $player->getLevel()->getName()) {
                    continue;
                }
                $distance = floor($player->distance($wayPoint));
                $message[] = TextFormat::BOLD . TextFormat::GOLD . $wayPoint->getName() . TextFormat::RESET . TextFormat::YELLOW . " ({$distance}m)";
            }
            $text = $player->getFloatingText("WayPoint");
            if(empty($message) and $text !== null) {
                $player->removeFloatingText("WayPoint");
                return;
            }
            elseif(empty($message) and $text === null) {
                return;
            }
            $message = implode("\n", $message);
            $directionVector = $player->getDirectionVector()->multiply(2);
            $position = Position::fromObject($player->add($directionVector->getX(), $player->getEyeHeight(), $directionVector->getZ()), $player->getLevel());
            if($text === null) {
                $player->addFloatingText($position, "WayPoint", $message);
                return;
            }
            $text->update($message);
            $text->move($position);
            $text->sendChangesTo($player);
        }
    }
}