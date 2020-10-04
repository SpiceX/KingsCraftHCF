<?php

namespace hcf\item\types;

use hcf\HCF;
use pocketmine\item\Snowball;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class TeleportationBall extends Snowball {

    /**
     * TeleportationBall constructor.
     *
     * @param int $meta
     */
    public function __construct(int $meta = 0) {
        $customName = TextFormat::RESET . TextFormat::AQUA . TextFormat::BOLD . "Teleportation Ball";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Hit a player with this to switch places.";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "You must be 7 or less blocks from the player.";
        $this->setCustomName($customName);
        $this->setLore($lore);
        parent::__construct($meta);
    }

    /**
     * @param Player $player
     * @param Vector3 $directionVector
     *
     * @return bool
     */
    public function onClickAir(Player $player, Vector3 $directionVector): bool {
        $areaManager = HCF::getInstance()->getAreaManager();
        $areas = $areaManager->getAreasInPosition($player->asPosition());
        if($areas !== null) {
            foreach($areas as $area) {
                if($area->getPvpFlag() === false) {
                    return false;
                }
            }
        }
        return parent::onClickAir($player, $directionVector);
    }

    /**
     * @return string
     */
    public function getProjectileEntityType(): string {
        return "TeleportationBall";
    }
}