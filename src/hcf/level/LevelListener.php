<?php /** @noinspection SuspiciousBinaryOperationInspection */

namespace hcf\level;

use Exception;
use hcf\HCF;
use hcf\HCFPlayer;
use hcf\level\tile\MobSpawner;
use hcf\translation\Translation;
use hcf\translation\TranslationException;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\Living;
use pocketmine\entity\object\ItemEntity;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDespawnEvent;
use pocketmine\event\entity\EntitySpawnEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\EnderPearl;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\tile\Tile;
use pocketmine\utils\TextFormat;

class LevelListener implements Listener
{

    /** @var HCF */
    private $core;

    /** @var Entity[] */
    private $entities = [];

    /** @var string[] */
    private $ids = [];

    /**
     * LevelListener constructor.
     *
     * @param HCF $core
     */
    public function __construct(HCF $core)
    {
        $this->core = $core;
    }

    /**
     * @param PlayerInteractEvent $event
     */
    public function onInteract(PlayerInteractEvent $event): void
    {
        $blockClicked = $event->getBlock();
        $item = $event->getItem();
        $blockReplace = $blockClicked->getSide($blockClicked->getDamage() ^ 0x01);
        /*if ($item->getId() === Item::ENDER_PEARL && $blockClicked->getId() === Block::FENCE_GATE) {
            $blockClicked->setDamage(1);
            $item->onActivate($event->getPlayer(), $blockReplace, $blockClicked, $event->getFace(), $event->getTouchVector());
            $event->setCancelled(true);
        }*/
        if ($item->getId() === Item::ENDER_PEARL && $blockClicked->getId() === Block::FENCE_GATE) {
            $event->setCancelled(true);
        }
    }

    /**
     * @param ProjectileLaunchEvent $event
     */
    public function onProjectileLaunch(ProjectileLaunchEvent $event): void
    {
        $entity = $event->getEntity();
        
    }

    /**
     * @param BlockUpdateEvent $event
     */
    public function onUpdateBlock(BlockUpdateEvent $event): void
    {
        $block = $event->getBlock();
        if ($block->getId() === Block::FENCE_GATE){
            $event->setCancelled();
        }
    }

    /**
     * @priority HIGHEST
     *
     * @param BlockPlaceEvent $event
     *
     * @throws TranslationException
     */
    public function onBlockPlace(BlockPlaceEvent $event): void
    {
        if ($event->isCancelled()) {
            return;
        }
        $block = $event->getBlock();
        $player = $event->getPlayer();
        if ($block->getId() !== Block::BEACON) {
            return;
        }
        if (!$player instanceof HCFPlayer) {
            return;
        }
        $claim = $this->core->getFactionManager()->getClaimInPosition($block);
        if ($claim !== null && $player->getFaction() !== null && $claim->getFaction()->getName() === $player->getFaction()->getName()) {
            return;
        }
        $event->setCancelled();
        $player->sendMessage(Translation::getMessage("placeBeaconInClaim"));
    }

    /**
     * @priority HIGHEST
     *
     * @param BlockBreakEvent $event
     *
     * @throws Exception
     */
    public function onBlockBreak(BlockBreakEvent $event): void
    {
        $block = $event->getBlock();
        if ($event->isCancelled()) {
            if ($this->core->getLevelManager()->getGlowstoneMountain()->inInside($block)) {
                $event->setCancelled(false);
            }
            return;
        }
        $player = $event->getPlayer();
        if ($block->getId() === Block::STONE) {
            if (random_int(1, 5000) === random_int(1, 5000)) {
                HCF::getInstance()->getScheduler()->scheduleDelayedTask(new class($player, $block) extends Task {

                    /** @var HCFPlayer */
                    private $player;

                    /** @var Position */
                    private $position;

                    /**
                     *  constructor.
                     *
                     * @param HCFPlayer $player
                     * @param Position $position
                     */
                    public function __construct(HCFPlayer $player, Position $position)
                    {
                        $this->player = $player;
                        $this->position = $position;
                    }

                    /**
                     * @param int $currentTick
                     */
                    public function onRun(int $currentTick): void
                    {
                        $types = [
                            Entity::CAVE_SPIDER,
                            Entity::RABBIT,
                            Entity::SKELETON,
                            Entity::SLIME,
                            Entity::COW,
                            Entity::ZOMBIE
                        ];
                        $type = $types[array_rand($types)];
                        $this->position->getLevel()->setBlock($this->position, Block::get(Block::MOB_SPAWNER), true, true);
                        $tile = $this->position->getLevel()->getTile($this->position);
                        if (!$tile instanceof MobSpawner) {
                            $nbt = MobSpawner::createNBT($this->position);
                            $nbt->setString(Tile::TAG_ID, Tile::MOB_SPAWNER);
                            /** @var MobSpawner $spawnerTile */
                            $tile = Tile::createTile("MobSpawner", $this->position->getLevel(), $nbt);
                        }
                        $tile->setSpawnEntityType($type);
                        $tile->spawnToAll();
                        $name = $tile->getEntityType();
                        $this->player->sendTitle(TextFormat::GREEN . "Spawner found!", TextFormat::GRAY . $name . " Spawner");
                    }
                }, 1);
            }
        }
    }

    /**
     * @priority HIGHEST
     *
     * @param EntitySpawnEvent $event
     */
    public function onEntitySpawn(EntitySpawnEvent $event): void
    {
        $entity = $event->getEntity();
        if ($entity instanceof Human) {
            return;
        }
        $uuid = uniqid('', true);
        if ($entity instanceof Living || $entity instanceof ItemEntity) {
            if (count($this->entities) > 400) {
                $despawn = array_shift($this->entities);
                if (!$despawn->isClosed()) {
                    $despawn->flagForDespawn();
                }
            }
            $this->ids[$entity->getId()] = $uuid;
            $this->entities[$uuid] = $entity;
            if (LevelManager::canStack($entity)) {
                LevelManager::addToStack($entity);
            }
        }
    }

    /**
     * @priority HIGHEST
     *
     * @param EntityDespawnEvent $event
     */
    public function onEntityDespawn(EntityDespawnEvent $event): void
    {
        $entity = $event->getEntity();
        if (!isset($this->ids[$entity->getId()])) {
            return;
        }
        $uuid = $this->ids[$entity->getId()];
        unset($this->ids[$entity->getId()]);
        if (isset($this->entities[$uuid])) {
            unset($this->entities[$uuid]);
        }
    }

    /**
     * @priority LOWEST
     *
     * @param EntityDamageEvent $event
     */
    public function onEntityDamage(EntityDamageEvent $event): void
    {
        $entity = $event->getEntity();
        if ($event instanceof EntityDamageByEntityEvent) {
            if ($entity->getHealth() <= $event->getFinalDamage() and $entity->namedtag->hasTag(LevelManager::STACK_TAG) and
                $entity instanceof Living) {
                $damager = $event->getDamager();
                if ($damager instanceof Player) {
                    $damager->addXp($entity->getXpDropAmount() * 1.5);
                }
                LevelManager::decreaseStackSize($entity);
                $event->setCancelled();
            }
        }
    }
}
