<?php

namespace hcf\level\populator;

use pocketmine\block\Block;
use pocketmine\block\Liquid;
use pocketmine\level\ChunkManager;
use pocketmine\level\generator\populator\Populator;
use pocketmine\utils\Random;

class LiquidPopulator extends Populator {

    /** @var ChunkManager */
    private $level;

    /** @var int */
    private $randomAmount;

    /** @var int */
    private $baseAmount;

    /** @var Block */
    private $type;

    /**
     * LiquidPopulator constructor.
     *
     * @param Liquid $block
     */
    public function __construct(Liquid $block) {
        $this->type = $block;
    }

    /**
     * @param int $amount
     */
    public function setRandomAmount(int $amount) {
        $this->randomAmount = $amount;
    }

    /**
     * @param int $amount
     */
    public function setBaseAmount(int $amount) {
        $this->baseAmount = $amount;
    }

    /**
     * @param ChunkManager $level
     * @param int $chunkX
     * @param int $chunkZ
     * @param Random $random
     */
    public function populate(ChunkManager $level, int $chunkX, int $chunkZ, Random $random) {
        $this->level = $level;
        $amount = $random->nextRange(0, $this->randomAmount + 1) + $this->baseAmount;
        for($i = 0; $i < $amount; ++$i) {
            $x = $random->nextRange($chunkX << 4, ($chunkX << 4) + 15);
            $z = $random->nextRange($chunkZ << 4, ($chunkZ << 4) + 15);
            $y = $this->getHighestWorkableBlock($x, $z);
            if($y === -1) {
                continue;
            }
            $this->level->setBlockIdAt($x, $y, $z, $this->type->getId());
        }
    }

    /**
     * @param int $x
     * @param int $z
     *
     * @return int
     */
    private function getHighestWorkableBlock(int $x, int $z): int {
        return mt_rand(1, 36);
    }
}