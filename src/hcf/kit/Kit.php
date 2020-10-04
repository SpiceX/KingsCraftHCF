<?php

namespace hcf\kit;

use hcf\HCF;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;

abstract class Kit implements ItemIds {

    /** @var string */
    private $name;

    /** @var int */
    private $cooldown;

    /**
     * Kit constructor.
     *
     * @param string $name
     * @param int $cooldown
     */
    public function __construct(string $name, int $cooldown) {
        $this->name = $name;
        $this->cooldown = $cooldown;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name ?? "";
    }

    /**
     * @return int
     */
    public function getCooldown(): int {
        return $this->cooldown ?? 0;
    }

    /**
     * @return Item[]
     */
    abstract public function getItems(): array;

    /**
     * @return HCF
     */
    public function getPlugin(): HCF
    {
        return HCF::getInstance();
    }
}