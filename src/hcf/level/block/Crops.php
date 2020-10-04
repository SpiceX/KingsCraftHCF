<?php

namespace hcf\level\block;

use pocketmine\event\block\BlockGrowEvent;
use ReflectionException;

abstract class Crops extends \pocketmine\block\Crops {

    /**
     * @throws ReflectionException
     */
    public function onRandomTick(): void {
        if($this->meta < 0x07) {
            $block = clone $this;
            ++$block->meta;
            $ev = new BlockGrowEvent($this, $block);
            $ev->call();
            if(!$ev->isCancelled()) {
                $this->getLevel()->setBlock($this, $ev->getNewState(), true, true);
            }
        }
    }
}
