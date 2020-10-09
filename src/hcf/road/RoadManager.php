<?php

namespace hcf\road;

use Exception;
use hcf\HCF;
use pocketmine\block\Block;
use pocketmine\level\format\Chunk;
use pocketmine\level\Position;

class RoadManager {

    public const MIN_X_START = -100;
    public const MAX_X_START = 98; // 98 - 66
    public const MIN_Z_START = -69; //-96;
    public const MAX_Z_START = 106; //106 - 61
    public const ROAD_WIDTH = 32; // 19 - 32

    /** @var HCF */
    private $core;

    /**
     * self constructor.
     *
     * @param HCF $core
     */
    public function __construct(HCF $core) {
        $this->core = $core;
        $core->getServer()->getPluginManager()->registerEvents(new RoadListener($core), $core);
    }

    /**
     * @param Position $position
     *
     * @return bool
     */
    public function isInRoad(Position $position): bool {
        if($position->getLevel()->getName() === $this->core->getServer()->getDefaultLevel()->getName()) {
            $floorX = $position->getFloorX();
            $floorZ = $position->getFloorZ();
            if($floorX <= ((self::ROAD_WIDTH - 1) / 2) && $position->getFloorX() >= (0 - ((self::ROAD_WIDTH - 1) / 2))) {
                if($floorX >= self::MAX_Z_START || $position->getFloorZ() <= self::MIN_Z_START) {
                    return true;
                }
            }
            if($floorZ <= ((self::ROAD_WIDTH - 1) / 2) && $position->getFloorZ() >= (0 - ((self::ROAD_WIDTH - 1) / 2))) {
                if($floorZ >= self::MAX_X_START || $position->getFloorX() <= self::MIN_X_START) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param Chunk $chunk
     * @param int $maxY
     * @throws Exception
     */
    public static function buildRoad(Chunk $chunk, int $maxY): void {
        $chunkX = $chunk->getX();
        $chunkZ = $chunk->getZ();
        if(($chunkX === ((0 - ((self::ROAD_WIDTH - 1) / 2))) >> 4) || ($chunkX === ((self::ROAD_WIDTH - 1) / 2) >> 4)) {
            for($z = 0; $z < 16; $z++) {
                if((($chunkZ << 4) + $z) <= self::MIN_Z_START xor (($chunkZ << 4) + $z) >= self::MAX_Z_START) {
                    for($x = 0; $x < 16; $x++) {
                        if(((($chunkX << 4) + $x) >= (0 - ((self::ROAD_WIDTH - 1) / 2))) && ((($chunkX << 4) + $x) <= ((self::ROAD_WIDTH - 1) / 2))) {
                            for($y = 128; $y >= $maxY; $y--) {
                                if($chunk->getBlockId($x, $y, $z) !== Block::AIR) {
                                    $chunk->setBlock($x, $y, $z, Block::AIR);
                                }
                            }
                            if(((($chunkX << 4) + $x) === (0 - ((self::ROAD_WIDTH - 1) / 2))) xor ((($chunkX << 4) + $x) === ((self::ROAD_WIDTH - 1) / 2))) {
                                for($y = $maxY; $y > 0; $y--) {
                                    if($chunk->getBlockId($x, $y, $z) !== Block::AIR) {
                                        break;
                                    }
                                    $chunk->setBlock($x, $y, $z, Block::WOOL, 14);
                                }
                                continue;
                            }
                            if(((($chunkX << 4) + $x) === 0) && ((($chunkZ << 4) + $z) % 8 === 0)) {
                                $chunk->setBlock($x, $maxY, $z, Block::SEA_LANTERN);
                                continue;
                            }
                            $blocks = [
                                Block::get(Block::GRAVEL, 0),
                                Block::get(Block::COBBLESTONE, 0),
                                Block::get(Block::STONE, 0)
                            ];
                            $block = $blocks[array_rand($blocks)];
                            if(((self::ROAD_WIDTH - 1) / 2) - abs(($chunkX << 4) + $x) > floor(((self::ROAD_WIDTH - 1) / 2) / 2)) {
                                $chunk->setBlock($x, $maxY, $z, $block->getId(), $block->getDamage());
                                continue;
                            }
                            $rand = random_int(1, 2);
                            if($rand === 1 && (((self::ROAD_WIDTH - 1) / 2) - abs(($chunkX << 4) + $x) === floor(((self::ROAD_WIDTH - 1) / 2) / 2))) {
                                $blocks = [
                                    Block::get(Block::GRAVEL, 0),
                                    Block::get(Block::COBBLESTONE, 0),
                                    Block::get(Block::STONE, 0)
                                ];
                                $block = $blocks[array_rand($blocks)];
                            }
                            else {
                                $blocks = [
                                    Block::get(Block::TERRACOTTA, 5),
                                    Block::get(Block::TERRACOTTA, 13),
                                    Block::get(Block::GRASS, 0),
                                    Block::get(Block::WOODEN_PLANKS, 1)
                                ];
                                $block = $blocks[array_rand($blocks)];
                            }
                            $chunk->setBlock($x, $maxY, $z, $block->getId(), $block->getDamage());
                        }
                    }
                }
            }
        }
        if(($chunkZ === ((0 - ((self::ROAD_WIDTH - 1) / 2))) >> 4) || ($chunkZ === ((self::ROAD_WIDTH - 1) / 2) >> 4)) {
            for($x = 0; $x < 16; $x++) {
                if((($chunkX << 4) + $x) <= self::MIN_X_START xor (($chunkX << 4) + $x) >= self::MAX_X_START) {
                    for($z = 0; $z < 16; $z++) {
                        if(((($chunkZ << 4) + $z) >= (0 - ((self::ROAD_WIDTH - 1) / 2))) && ((($chunkZ << 4) + $z) <= ((self::ROAD_WIDTH - 1) / 2))) {
                            for($y = 128; $y >= $maxY; $y--) {
                                if($chunk->getBlockId($x, $y, $z) !== Block::AIR) {
                                    $chunk->setBlock($x, $y, $z, Block::AIR);
                                }
                            }
                            if(((($chunkZ << 4) + $z) === (0 - ((self::ROAD_WIDTH - 1) / 2))) xor ((($chunkZ << 4) + $z) === ((self::ROAD_WIDTH - 1) / 2))) {
                                for($y = $maxY; $y > 0; $y--) {
                                    if($chunk->getBlockId($x, $y, $z) !== Block::AIR) {
                                        break;
                                    }
                                    $chunk->setBlock($x, $y, $z, Block::WOOL, 14);
                                }
                                continue;
                            }
                            if(((($chunkZ << 4) + $z) === 0) && ((($chunkX << 4) + $x) % 8 === 0)) {
                                $chunk->setBlock($x, $maxY, $z, Block::SEA_LANTERN);
                                continue;
                            }
                            $blocks = [
                                Block::get(Block::GRAVEL, 0),
                                Block::get(Block::COBBLESTONE, 0),
                                Block::get(Block::STONE, 0)
                            ];
                            $block = $blocks[array_rand($blocks)];
                            if(((self::ROAD_WIDTH - 1) / 2) - abs(($chunkZ << 4) + $z) > floor(((self::ROAD_WIDTH - 1) / 2) / 2)) {
                                $chunk->setBlock($x, $maxY, $z, $block->getId(), $block->getDamage());
                                continue;
                            }
                            $rand = random_int(1, 2);
                            if($rand === 1 && (((self::ROAD_WIDTH - 1) / 2) - abs(($chunkZ << 4) + $z) === floor(((self::ROAD_WIDTH - 1) / 2) / 2))) {
                                $blocks = [
                                    Block::get(Block::GRAVEL, 0),
                                    Block::get(Block::COBBLESTONE, 0),
                                    Block::get(Block::STONE, 0)
                                ];
                                $block = $blocks[array_rand($blocks)];
                            }
                            else {
                                $blocks = [
                                    Block::get(Block::TERRACOTTA, 5),
                                    Block::get(Block::TERRACOTTA, 13),
                                    Block::get(Block::GRASS, 0),
                                    Block::get(Block::WOODEN_PLANKS, 1)
                                ];
                                $block = $blocks[array_rand($blocks)];
                            }
                            $chunk->setBlock($x, $maxY, $z, $block->getId(), $block->getDamage());
                        }
                    }
                }
            }
        }
    }
}