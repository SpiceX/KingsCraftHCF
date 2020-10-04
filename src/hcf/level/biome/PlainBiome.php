<?php

namespace hcf\level\biome;

use hcf\level\populator\LiquidPopulator;
use pocketmine\block\Lava;
use pocketmine\block\Sapling;
use pocketmine\block\Water;
use pocketmine\level\biome\Biome;
use pocketmine\level\generator\populator\Tree;

class PlainBiome extends \pocketmine\level\biome\PlainBiome {

    /**
     * PlainBiome constructor.
     */
    public function __construct() {
        parent::__construct();
        $trees = new Tree(Sapling::OAK);
        $trees->setBaseAmount(2);
        $this->addPopulator($trees);
        $ponds = new LiquidPopulator(new Water());
        $ponds->setBaseAmount(10);
        $this->addPopulator($ponds);
        $ponds = new LiquidPopulator(new Lava());
        $ponds->setBaseAmount(10);
        $this->addPopulator($ponds);
    }

    /**
     * @return string
     */
    public function getName(): string {
        return "Plains";
    }

    /**
     * @return int
     */
    public function getId(): int {
        return Biome::PLAINS;
    }
}