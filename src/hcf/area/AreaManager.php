<?php

namespace hcf\area;

use hcf\HCF;
use pocketmine\level\Position;

class AreaManager {

    /** @var HCF */
    private $core;

    /** @var Area[] */
    private $areas = [];

    /**
     * AreaManager constructor.
     *
     * @param HCF $core
     *
     * @throws AreaException
     */
    public function __construct(HCF $core) {
        $this->core = $core;
        $core->getServer()->getPluginManager()->registerEvents(new AreaListener($core), $core);
        $this->init();
    }

    /**
     * @throws AreaException
     * WEST ROAD: -65 0 16 / -2000 256 -16
     * SOUTH ROAD: 16 0 61 / -16 256 2000
     * EAST ROAD: 66, 0, -16 / 2000 256 16
     * NORTH ROAD: -16 0 -70 / 16 256 -2000
     */
    public function init(): void {
        $this->addArea(new Area("Spawn", new Position(65, 0, -69, $this->core->getServer()->getDefaultLevel()), new Position(-64, 256, 60, $this->core->getServer()->getDefaultLevel()), false, false));
        $this->addArea(new Area("West Road", new Position(-65, 0, 16, $this->core->getServer()->getDefaultLevel()), new Position(-2000, 256, -16, $this->core->getServer()->getDefaultLevel()), true, false));
        $this->addArea(new Area("South Road", new Position(16, 0, 61, $this->core->getServer()->getDefaultLevel()), new Position(-16, 256, 2000, $this->core->getServer()->getDefaultLevel()), true, false));
        $this->addArea(new Area("East Road", new Position(66, 0, -16, $this->core->getServer()->getDefaultLevel()), new Position(2000, 256, 16, $this->core->getServer()->getDefaultLevel()), true, false));
        $this->addArea(new Area("North Road", new Position(-16, 0, -70, $this->core->getServer()->getDefaultLevel()), new Position(16, 256, -2000, $this->core->getServer()->getDefaultLevel()), true, false));
        $this->addArea(new Area("(1498, 1505) End Portal", new Position(1492, 0, 1500, $this->core->getServer()->getDefaultLevel()), new Position(1502, 256, 1510, $this->core->getServer()->getDefaultLevel()), true, false));
        //$this->addArea(new Area("(-750, 750) End Portal", new Position(-748, 0, 748, $this->core->getServer()->getDefaultLevel()), new Position(-752, 256, 752, $this->core->getServer()->getDefaultLevel()), true, false));
        //$this->addArea(new Area("(-750, -750) End Portal", new Position(-748, 0, -748, $this->core->getServer()->getDefaultLevel()), new Position(-752, 256, -752, $this->core->getServer()->getDefaultLevel()), true, false));
        //$this->addArea(new Area("(750, -750) End Portal", new Position(748, 0, -748, $this->core->getServer()->getDefaultLevel()), new Position(752, 256, -752, $this->core->getServer()->getDefaultLevel()), true, false));
        $this->addArea(new Area("End Spawn", new Position(36, 0, 73, $this->core->getServer()->getLevelByName("ender")), new Position(52, 256, 89, $this->core->getServer()->getLevelByName("ender")), false, false));
        $this->addArea(new Area("Nether Spawn", new Position(77, 0, 62, $this->core->getServer()->getLevelByName("nether")), new Position(-85, 256, 59, $this->core->getServer()->getLevelByName("nether")), false, false));
        $this->addArea(new Area("Greek KOTH", new Position(527, 0, -448, $this->core->getServer()->getDefaultLevel()), new Position(512, 256, -433, $this->core->getServer()->getDefaultLevel()), true, false));
        $this->addArea(new Area("Ruins KOTH", new Position(474, 0, 477, $this->core->getServer()->getDefaultLevel()), new Position(574, 256, 577, $this->core->getServer()->getDefaultLevel()), true, false));
        $this->addArea(new Area("Sakura KOTH", new Position(-422, 0, 422, $this->core->getServer()->getDefaultLevel()), new Position(-578, 256, 578, $this->core->getServer()->getDefaultLevel()), true, false));
        $this->addArea(new Area("Medieval KOTH", new Position(-422, 0, -381, $this->core->getServer()->getDefaultLevel()), new Position(-652, 256, -612, $this->core->getServer()->getDefaultLevel()), true, false));
    }

    /**
     * @param Area $area
     */
    public function addArea(Area $area): void {
        $this->areas[] = $area;
    }

    /**
     * @param Position $position
     *
     * @return Area[]|null
     */
    public function getAreasInPosition(Position $position): ?array {
        $areas = $this->getAreas();
        $areasInPosition = [];
        foreach($areas as $area) {
            if($area->isPositionInside($position) === true) {
                $areasInPosition[] = $area;
            }
        }
        if(empty($areasInPosition)) {
            return null;
        }
        return $areasInPosition;
    }

    /**
     * @return Area[]
     */
    public function getAreas(): array {
        return $this->areas;
    }
}