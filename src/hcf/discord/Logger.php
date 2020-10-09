<?php

namespace hcf\discord;

use hcf\HCF;
use pocketmine\event\Listener;

class Logger implements Listener
{

    private $core;

    public function __construct(HCF $core)
    {
        $this->core = $core;
    }

}