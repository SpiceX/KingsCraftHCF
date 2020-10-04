<?php

namespace hcf\network;

use hcf\HCF;
use hcf\network\packets\CraftingDataPacket;
use hcf\network\packets\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\PacketPool;

class NetworkManager {

    /** @var HCF */
    private $core;

    /**
     * NetworkManager constructor.
     *
     * @param HCF $core
     */
    public function __construct(HCF $core) {
        $this->core = $core;
        $this->init();
        $core->getServer()->getPluginManager()->registerEvents(new NetworkListener($core), $core);
    }

    public function init() {
        PacketPool::registerPacket(new CraftingDataPacket());
        PacketPool::registerPacket(new InventoryTransactionPacket());
    }
}