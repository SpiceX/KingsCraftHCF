<?php

namespace hcf\level;

use Exception;
use hcf\HCF;
use pocketmine\block\Block;
use pocketmine\level\Position;
use pocketmine\math\Vector3;

class GlowstoneMountain {

    /** @var Position */
    private $firstPosition;

    /** @var Position */
    private $secondPosition;

    /**
     * GlowstoneMountain constructor.
     *
     * @param Position $firstPosition
     * @param Position $secondPosition
     */
    public function __construct(Position $firstPosition, Position $secondPosition) {
        $this->firstPosition = $firstPosition;
        $this->secondPosition = $secondPosition;
    }

    /**
     * @param Position $position
     *
     * @return bool
     */
    public function inInside(Position $position): bool {
        $level = $position->getLevel();
        $firstPosition = $this->firstPosition;
        $secondPosition = $this->secondPosition;
        $minX = min($firstPosition->getX(), $secondPosition->getX());
        $maxX = max($firstPosition->getX(), $secondPosition->getX());
        $minY = min($firstPosition->getY(), $secondPosition->getY());
        $maxY = max($firstPosition->getY(), $secondPosition->getY());
        $minZ = min($firstPosition->getZ(), $secondPosition->getZ());
        $maxZ = max($firstPosition->getZ(), $secondPosition->getZ());
        return $minX <= $position->getX() and $maxX >= $position->getX() and $minY <= $position->getY() and
            $maxY >= $position->getY() and $minZ <= $position->getZ() and $maxZ >= $position->getZ() and
            $level->getName() === "nether";
    }

    /**
     * @return Position
     */
    public function getFirstPosition(): Position {
        return $this->firstPosition;
    }

    /**
     * @return Position
     */
    public function getSecondPosition(): Position {
        return $this->secondPosition;
    }

    /**
     * @throws Exception
     * @noinspection RandomApiMigrationInspection
     * @noinspection SuspiciousBinaryOperationInspection
     */
    public function reset(): void
    {
        $firstPosition = $this->firstPosition;
        $secondPosition = $this->secondPosition;
        $level = HCF::getInstance()->getServer()->getLevelByName("nether");
        foreach($level->getPlayers() as $player) {
            if($this->inInside($player)) {
                $player->teleport($level->getSpawnLocation());
            }
        }
        $minX = min($firstPosition->getX(), $secondPosition->getX());
        $maxX = max($firstPosition->getX(), $secondPosition->getX());
        $minY = min($firstPosition->getY(), $secondPosition->getY());
        $maxY = max($firstPosition->getY(), $secondPosition->getY());
        $minZ = min($firstPosition->getZ(), $secondPosition->getZ());
        $maxZ = max($firstPosition->getZ(), $secondPosition->getZ());
        for($x = $minX; $x <= $maxX; $x++) {
            for($y = $minY; $y <= $maxY; $y++) {
                for($z = $minZ; $z <= $maxZ; $z++) {
                    $position = new Vector3($x, $y, $z);
                    if(mt_rand(1, 2) === mt_rand(1, 2)) {
                        $level->setBlock($position, Block::get(Block::SOUL_SAND));
                        continue;
                    }
                    $level->setBlock($position, Block::get(Block::GLOWSTONE));
                }
            }
        }
    }
}
