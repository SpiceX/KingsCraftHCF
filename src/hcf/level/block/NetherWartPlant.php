<?php

namespace hcf\level\block;

use pocketmine\event\block\BlockGrowEvent;
use ReflectionException;

class NetherWartPlant extends \pocketmine\block\NetherWartPlant {

    /**
     * @throws ReflectionException
     */
    public function onRandomTick(): void {
        if($this->meta < 3 and mt_rand(0, 1) === 0) {
            $block = clone $this;
            $block->meta++;
            $ev = new BlockGrowEvent($this, $block);
            $ev->call();
            if(!$ev->isCancelled()) {
                $this->getLevel()->setBlock($this, $ev->getNewState(), false, true);
            }
        }
    }
}