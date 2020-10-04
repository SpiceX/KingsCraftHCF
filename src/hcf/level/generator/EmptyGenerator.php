<?php

namespace hcf\level\generator;

use pocketmine\level\generator\Generator;
use pocketmine\level\generator\InvalidGeneratorOptionsException;
use pocketmine\math\Vector3;

class EmptyGenerator extends Generator {

    /**
     * @param array $options
     *
     * @throws InvalidGeneratorOptionsException
     */
    public function __construct(array $options = []) {
    }

    /**
     * @return string
     */
    public function getName(): string {
        return "empty";
    }

    /**
     * @return array
     */
    public function getSettings(): array {
        return [];
    }

    /**
     * @param int $chunkX
     * @param int $chunkZ
     */
    public function generateChunk(int $chunkX, int $chunkZ): void {

    }

    /**
     * @param int $chunkX
     * @param int $chunkZ
     */
    public function populateChunk(int $chunkX, int $chunkZ): void {

    }

    /**
     * @return Vector3
     */
    public function getSpawn(): Vector3 {
        return new Vector3(127.5, 128, 127.5);
    }
}