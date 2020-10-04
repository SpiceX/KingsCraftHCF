<?php

namespace hcf\level\generator;

use hcf\level\biome\PlainBiome;
use hcf\road\RoadManager;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\level\biome\Biome;
use pocketmine\level\ChunkManager;
use pocketmine\level\generator\Generator;
use pocketmine\level\generator\InvalidGeneratorOptionsException;
use pocketmine\level\generator\noise\Simplex;
use pocketmine\level\generator\object\OreType;
use pocketmine\level\generator\populator\GroundCover;
use pocketmine\level\generator\populator\Ore;
use pocketmine\level\generator\populator\Populator;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;
use function exp;

/**
 * From PMMP
 */
class NormalGenerator extends Generator {

    /** @var Populator[] */
    private $populators = [];

    /** @var int */
    private $waterHeight = 62;

    /** @var Populator[] */
    private $generationPopulators = [];

    /** @var Simplex */
    private $noiseBase;

    /** @var Biome */
    private $biome;

    private static $GAUSSIAN_KERNEL = null;

    private static $SMOOTH_SIZE = 2;

    /**
     * @param array $options
     *
     * @throws InvalidGeneratorOptionsException
     */
    public function __construct(array $options = []) {
        if(self::$GAUSSIAN_KERNEL === null) {
            self::generateKernel();
        }
    }

    private static function generateKernel(): void {
        self::$GAUSSIAN_KERNEL = [];
        $bellSize = 1 / self::$SMOOTH_SIZE;
        $bellHeight = 2 * self::$SMOOTH_SIZE;
        for($sx = -self::$SMOOTH_SIZE; $sx <= self::$SMOOTH_SIZE; ++$sx) {
            self::$GAUSSIAN_KERNEL[$sx + self::$SMOOTH_SIZE] = [];
            for($sz = -self::$SMOOTH_SIZE; $sz <= self::$SMOOTH_SIZE; ++$sz) {
                $bx = $bellSize * $sx;
                $bz = $bellSize * $sz;
                self::$GAUSSIAN_KERNEL[$sx + self::$SMOOTH_SIZE][$sz + self::$SMOOTH_SIZE] = $bellHeight * exp(-($bx * $bx + $bz * $bz) / 2);
            }
        }
    }

    /**
     * @return string
     */
    public function getName(): string {
        return "normal";
    }

    /**
     * @return array
     */
    public function getSettings(): array {
        return [];
    }

    /**
     * @param ChunkManager $level
     * @param Random $random
     */
    public function init(ChunkManager $level, Random $random): void {
        parent::init($level, $random);
        $this->random->setSeed($this->level->getSeed());
        $this->noiseBase = new Simplex($this->random, 4, 1 / 4, 1 / 32);
        $this->random->setSeed($this->level->getSeed());
        $this->biome = new PlainBiome();
        $cover = new GroundCover();
        $this->generationPopulators[] = $cover;
        $ores = new Ore();
        $ores->setOreTypes([
            new OreType(BlockFactory::get(Block::COAL_ORE), 20, 20, 0, 128),
            new OreType(BlockFactory::get(Block::IRON_ORE), 20, 16, 0, 64),
            new OreType(BlockFactory::get(Block::REDSTONE_ORE), 8, 14, 0, 16),
            new OreType(BlockFactory::get(Block::LAPIS_ORE), 6, 12, 0, 32),
            new OreType(BlockFactory::get(Block::GOLD_ORE), 4, 10, 0, 32),
            new OreType(BlockFactory::get(Block::DIAMOND_ORE), 3, 10, 0, 16),
            new OreType(BlockFactory::get(Block::EMERALD_ORE), 3, 8, 0, 16),
            new OreType(BlockFactory::get(Block::DIRT), 20, 32, 0, 128),
            new OreType(BlockFactory::get(Block::GRAVEL), 10, 16, 0, 128),
            new OreType(BlockFactory::get(Block::SAND), 12, 32, 0, 128)
        ]);
        $this->populators[] = $ores;
    }

    /**
     * @param int $chunkX
     * @param int $chunkZ
     */
    public function generateChunk(int $chunkX, int $chunkZ): void {
        $this->random->setSeed(0xdeadbeef ^ ($chunkX << 8) ^ $chunkZ ^ $this->level->getSeed());
        $noise = $this->noiseBase->getFastNoise3D(16, 128, 16, 4, 8, 4, $chunkX * 16, 0, $chunkZ * 16);
        $chunk = $this->level->getChunk($chunkX, $chunkZ);
        $biomeCache = [];
        for($x = 0; $x < 16; ++$x) {
            for($z = 0; $z < 16; ++$z) {
                $minSum = 0;
                $maxSum = 0;
                $weightSum = 0;
                $chunk->setBiomeId($x, $z, $this->biome->getId());
                for($sx = -self::$SMOOTH_SIZE; $sx <= self::$SMOOTH_SIZE; ++$sx) {
                    for($sz = -self::$SMOOTH_SIZE; $sz <= self::$SMOOTH_SIZE; ++$sz) {
                        $weight = self::$GAUSSIAN_KERNEL[$sx + self::$SMOOTH_SIZE][$sz + self::$SMOOTH_SIZE];
                        if($sx === 0 and $sz === 0) {
                            $adjacent = $this->biome;
                        }
                        else {
                            $index = Level::chunkHash($chunkX * 16 + $x + $sx, $chunkZ * 16 + $z + $sz);
                            if(isset($biomeCache[$index])) {
                                $adjacent = $biomeCache[$index];
                            }
                            else {
                                $biomeCache[$index] = $adjacent = $this->biome;
                            }
                        }
                        $minSum += ($adjacent->getMinElevation() - 1) * $weight;
                        $maxSum += $adjacent->getMaxElevation() * $weight;
                        $weightSum += $weight;
                    }
                }
                $minSum /= $weightSum;
                $maxSum /= $weightSum;
                $smoothHeight = ($maxSum - $minSum) / 2;
                for($y = 0; $y < 128; ++$y) {
                    if($y === 0) {
                        $chunk->setBlockId($x, $y, $z, Block::BEDROCK);
                        continue;
                    }
                    $noiseValue = $noise[$x][$z][$y] - 1 / $smoothHeight * ($y - $smoothHeight - $minSum);
                    if($noiseValue > 0) {
                        $chunk->setBlockId($x, $y, $z, Block::STONE);
                    }
                    elseif($y <= $this->waterHeight) {
                        $chunk->setBlockId($x, $y, $z, Block::STILL_WATER);
                    }
                }
            }
        }
        foreach($this->generationPopulators as $populator) {
            $populator->populate($this->level, $chunkX, $chunkZ, $this->random);
        }
    }

    /**
     * @param int $chunkX
     * @param int $chunkZ
     */
    public function populateChunk(int $chunkX, int $chunkZ): void {
        if(abs($chunkX) > ((RoadManager::ROAD_WIDTH >> 4) + 2) and abs($chunkZ) > ((RoadManager::ROAD_WIDTH >> 4) + 2)) {
            $this->random->setSeed(0xdeadbeef ^ ($chunkX << 8) ^ $chunkZ ^ $this->level->getSeed());
            foreach($this->populators as $populator) {
                $populator->populate($this->level, $chunkX, $chunkZ, $this->random);
            }
            $this->biome->populateChunk($this->level, $chunkX, $chunkZ, $this->random);
        }
    }

    /**
     * @return Vector3
     */
    public function getSpawn(): Vector3 {
        return new Vector3(0, 128, 0);
    }
}