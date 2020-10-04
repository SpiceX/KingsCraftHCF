<?php

namespace hcf\watchdog;

use hcf\HCF;

class WatchdogManager {

    /** @var HCF */
    private $core;

    /**
     * WatchdogManager constructor.
     *
     * @param HCF $core
     */
    public function __construct(HCF $core) {
        $this->core = $core;
        $core->getServer()->getPluginManager()->registerEvents(new WatchdogListener($core), $core);
    }
}
