<?php

namespace hcf\level\biome;

use pocketmine\level\biome\Biome;

class EnderBiome extends Biome {

    /**
     * @return string
     */
    public function getName(): string {
        return "Ender";
    }

    /**
     * @return int
     */
    public function getId(): int {
        return 9;
    }
}