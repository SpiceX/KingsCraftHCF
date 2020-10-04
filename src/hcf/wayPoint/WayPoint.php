<?php

namespace hcf\wayPoint;

use pocketmine\level\Level;
use pocketmine\level\Position;

class WayPoint extends Position {

    /** @var string */
    private $name;

    /**
     * WayPoint constructor.
     *
     * @param string $name
     * @param int $x
     * @param int $y
     * @param int $z
     * @param Level $level
     */
    public function __construct(string $name, int $x, int $y, int $z, Level $level) {
        $this->name = $name;
        parent::__construct($x, $y, $z, $level);
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }
}