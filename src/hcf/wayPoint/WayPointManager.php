<?php

namespace hcf\wayPoint;

use hcf\HCF;
use hcf\wayPoint\task\WayPointMoveTask;

class WayPointManager {

    /** @var HCF */
    private $core;

    /**
     * WayPointManager constructor.
     *
     * @param HCF $core
     */
    public function __construct(HCF $core) {
        $this->core = $core;
        $core->getScheduler()->scheduleRepeatingTask(new WayPointMoveTask(), 10);
    }
}
