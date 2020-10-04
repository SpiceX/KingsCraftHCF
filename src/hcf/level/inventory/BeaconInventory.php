<?php

namespace hcf\level\inventory;

use hcf\level\tile\Beacon;
use pocketmine\inventory\ContainerInventory;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\WindowTypes;

class BeaconInventory extends ContainerInventory {

    /**
     * BeaconInventory constructor.
     *
     * @param Beacon $tile
     */
    public function __construct(Beacon $tile){
        parent::__construct($tile);
    }

    /**
     * @return int
     */
    public function getNetworkType(): int{
        return WindowTypes::BEACON;
    }

    /**
     * @return string
     */
    public function getName(): string{
        return "Beacon";
    }

    /**
     * @return int
     */
    public function getDefaultSize(): int{
        return 1;
    }

    /**
     * @return Vector3
     */
    public function getHolder(){
        return $this->holder;
    }
}