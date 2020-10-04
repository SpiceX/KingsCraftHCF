<?php

namespace hcf\update;

use hcf\HCF;
use hcf\HCFPlayer;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;

class UpdateListener implements Listener {

    /** @var HCF */
    private $core;

    /**
     * UpdateListener constructor.
     *
     * @param HCF $core
     */
    public function __construct(HCF $core) {
        $this->core = $core;
    }

    /**
     * @priority NORMAL
     * @param PlayerJoinEvent $event
     */
    public function onPlayerJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof HCFPlayer) {
            return;
        }
  
    }
}