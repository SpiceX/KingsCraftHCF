<?php

namespace hcf\koth;

use hcf\HCF;
use hcf\koth\task\KOTHHeartbeatTask;
use hcf\koth\task\KOTHStartGameTask;
use hcf\translation\Translation;
use hcf\translation\TranslationException;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\utils\TextFormat;

class KOTHManager {

    /** @var HCF */
    private $core;

    /** @var KOTHArena[] */
    private $arenas = [];

    /** @var null|KOTHArena */
    private $game = null;

    /**
     * KOTHManager constructor.
     *
     * @param HCF $core
     *
     * @throws KOTHException
     */
    public function __construct(HCF $core) {
        $this->core = $core;
        $this->init();
        $this->core->getScheduler()->scheduleRepeatingTask(new KOTHHeartbeatTask($this), 20);
        $this->core->getScheduler()->scheduleDelayedTask(new KOTHStartGameTask($this), 20);
    }

    /**
     * @throws KOTHException
     */
    public function init(): void {
        $this->arenas[] = new KOTHArena("Ruins", new Position(521, 0, 524, $this->core->getServer()->getDefaultLevel()), new Position(527, Level::Y_MAX, 530, $this->core->getServer()->getDefaultLevel()), 300);
        $this->arenas[] = new KOTHArena("Greek", new Position(516, 0, -439, $this->core->getServer()->getDefaultLevel()), new Position(520, Level::Y_MAX, -442, $this->core->getServer()->getDefaultLevel()), 300);
        $this->arenas[] = new KOTHArena("Sakura", new Position(-497, 0, 497, $this->core->getServer()->getDefaultLevel()), new Position(-503, Level::Y_MAX, 503, $this->core->getServer()->getDefaultLevel()), 300);
        $this->arenas[] = new KOTHArena("Medieval", new Position(-510, 0, -468, $this->core->getServer()->getDefaultLevel()), new Position(-504, Level::Y_MAX, -474, $this->core->getServer()->getDefaultLevel()), 300);
        $this->arenas[] = new KOTHArena("End", new Position(-15, 0, -3, $this->core->getServer()->getLevelByName("ender")), new Position(-21, Level::Y_MAX, 3, $this->core->getServer()->getLevelByName("ender")), 600);
    }

    /**
     * @return KOTHArena[]
     */
    public function getArenas(): array {
        return $this->arenas;
    }

    /**
     * @throws TranslationException
     */
    public function startEndOfTheWorldKOTH(): void {
        $eotwArena = null;
        foreach($this->arenas as $arena) {
            if($arena->getName() === "End") {
                $eotwArena = $arena;
            }
        }
        if($eotwArena === null) {
            return;
        }
        $this->game = $eotwArena;
        $this->core->getServer()->broadcastMessage(Translation::getMessage("kothBegin", [
            "name" => TextFormat::LIGHT_PURPLE . $eotwArena->getName()
        ]));
    }

    /**
     * @throws TranslationException
     */
    public function startGame(): void {
        if(empty($this->arenas)) {
            return;
        }
        $arena = $this->arenas[array_rand($this->arenas)];
        $this->game = $arena;
        $this->core->getServer()->broadcastMessage(Translation::getMessage("kothBegin", [
            "name" => TextFormat::LIGHT_PURPLE . $arena->getName()
        ]));
    }

    public function endGame(): void {
        $this->game = null;
        $this->core->getScheduler()->scheduleDelayedTask(new KOTHStartGameTask($this), 432000);
    }

    /**
     * @return KOTHArena|null
     */
    public function getGame(): ?KOTHArena {
        return $this->game;
    }
}