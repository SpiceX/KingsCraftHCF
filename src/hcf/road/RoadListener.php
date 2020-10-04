<?php

namespace hcf\road;

use hcf\HCF;
use hcf\HCFPlayer;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\level\ChunkPopulateEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat as TF;

class RoadListener implements Listener {

    /** @var HCF */
    private $core;

    /**
     * RoadListener constructor.
     *
     * @param HCF $core
     */
    public function __construct(HCF $core) {
        $this->core = $core;
    }

    /**
     * @priority HIGH
     * @param PlayerMoveEvent $event
     *
     */
    public function onPlayerMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof HCFPlayer) {
            return;
        }
        $region = $player->getRegion();
        if($region !== $player->getRegionByPosition()) {
            $player->setRegion($player->getRegionByPosition());
            $player->sendMessage(TF::GRAY . "Now entering: " . TF::RED . $player->getRegionByPosition());
        }
    }

    /**
     * @priority HIGH
     * @param PlayerInteractEvent $event
     */
    public function onPlayerInteract(PlayerInteractEvent $event): void {
        if($event->getPlayer()->isOp()) {
            return;
        }
        $block = $event->getBlock();
        $item = $event->getItem();
        if(($item->getId() === Item::BUCKET || $item->getId() === Item::FLINT_AND_STEEL) && $this->core->getRoadManager()->isInRoad($block->asPosition())) {
            $event->setCancelled();
        }
    }

    /**
     * @priority HIGH
     * @param BlockBreakEvent $event
     */
    public function onBlockBreak(BlockBreakEvent $event): void {
        if($event->getPlayer()->isOp()) {
            return;
        }
        $block = $event->getBlock();
        if($this->core->getRoadManager()->isInRoad($block->asPosition())) {
            $event->setCancelled();
        }
    }

    /**
     * @priority HIGH
     * @param BlockPlaceEvent $event
     */
    public function onBlockPlace(BlockPlaceEvent $event): void {
        if($event->getPlayer()->isOp()) {
            return;
        }
        $block = $event->getBlock();
        if($this->core->getRoadManager()->isInRoad($block->asPosition())) {
            $event->setCancelled();
        }
    }

    /**
     * @priority NORMAL
     * @param ChunkPopulateEvent $event
     */
    public function onChunkPopulate(ChunkPopulateEvent $event): void {
        $level = $event->getLevel();
        if($level->getName() !== $this->core->getServer()->getDefaultLevel()->getName()) {
            return;
        }
        $chunk = $event->getChunk();
        $chunkX = $chunk->getX();
        $chunkZ = $chunk->getZ();
        if((($chunkZ === ((0 - ((RoadManager::ROAD_WIDTH - 1) / 2))) >> 4) && ($chunkX >= (RoadManager::MAX_X_START >> 4) || $chunkX <= (RoadManager::MIN_X_START >> 4))) || (($chunkZ === ((RoadManager::ROAD_WIDTH - 1) / 2) >> 4) && ($chunkX >= (RoadManager::MAX_X_START >> 4) || $chunkX <= (RoadManager::MIN_X_START >> 4))) || (($chunkX === ((0 - ((RoadManager::ROAD_WIDTH - 1) / 2))) >> 4) && ($chunkZ >= (RoadManager::MAX_Z_START >> 4) || $chunkZ <= (RoadManager::MIN_Z_START >> 4))) || (($chunkX === ((RoadManager::ROAD_WIDTH - 1) / 2) >> 4) && ($chunkZ >= (RoadManager::MAX_Z_START >> 4) || $chunkZ <= (RoadManager::MIN_Z_START >> 4)))) {
            $maxY = $level->getSpawnLocation()->getFloorY();
            RoadManager::buildRoad($chunk, $maxY);
        }
    }
}