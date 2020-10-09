<?php


namespace hcf\level\inventory;


interface FakeInventory
{

    /**
     * @return int[]
     */
    public function getUIOffsets() : array;
}