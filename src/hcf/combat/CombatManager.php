<?php

namespace hcf\combat;

use hcf\combat\entity\LogoutVillager;
use hcf\HCF;
use pocketmine\entity\Entity;

class CombatManager {

    /** @var HCF */
    private $core;
    /** @var CombatListener */
    private $combatListener;

    /**
     * CombatManager constructor.
     *
     * @param HCF $core
     */
    public function __construct(HCF $core) {
        $this->core = $core;
        Entity::registerEntity(LogoutVillager::class, true);
        $core->getServer()->getPluginManager()->registerEvents($this->combatListener = new CombatListener($core), $core);
    }

    /**
     * @return HCF
     */
    public function getCore(): HCF
    {
        return $this->core;
    }

    /**
     * @return CombatListener
     */
    public function getCombatListener(): CombatListener
    {
        return $this->combatListener;
    }
}