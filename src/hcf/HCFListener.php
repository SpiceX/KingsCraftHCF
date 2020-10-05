<?php /** @noinspection NullPointerExceptionInspection */

namespace hcf;

use Exception;
use hcf\translation\Translation;
use hcf\translation\TranslationException;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\entity\object\ExperienceOrb;
use pocketmine\entity\object\ItemEntity;
use pocketmine\entity\object\PrimedTNT;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\entity\EntitySpawnEvent;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\level\ChunkLoadEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\particle\Particle;
use pocketmine\level\Position;
use pocketmine\level\sound\EndermanTeleportSound;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\ChangeDimensionPacket;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\scheduler\Task;
use pocketmine\tile\Sign;
use pocketmine\utils\TextFormat;

class HCFListener implements Listener
{

    /** @var HCF */
    private $core;

    /** @var string[] */
    private $players = [];

    /** @var int[] */
    private $command = [];

    /** @var int[] */
    private $chat = [];

    /** @var bool */
    private $blocks = [];

    /**
     * HCFListener constructor.
     *
     * @param HCF $core
     */
    public function __construct(HCF $core)
    {
        $this->core = $core;
    }

    /**
     * @priority LOW
     * @param PlayerPreLoginEvent $event
     */
    public function onPlayerPreLogin(PlayerPreLoginEvent $event): void
    {
        $player = $event->getPlayer();
        if (!$this->core->getServer()->isWhitelisted($player->getName())) {
            $event->setKickMessage(TextFormat::RESET . TextFormat::RED . "Server is white-listed! We will keep you updated.\n" . TextFormat::AQUA . "Twitter: " . TextFormat::WHITE . "ComingSoon\n" . TextFormat::LIGHT_PURPLE . "Discord: " . TextFormat::WHITE . "https://bit.ly/30y8buf");
            $event->setCancelled();
            return;
        }
        if (($this->core->isEndOfTheWorld() === true) && isset($this->players[$player->getRawUniqueId()])) {
            $event->setKickMessage(TextFormat::RED . "You've died in EOTW! You can't rejoin!");
            $event->setCancelled();
            return;
        }
    }

    /**
     * @priority LOWEST
     * @param PlayerLoginEvent $event
     *
     * @throws TranslationException
     */
    public function onPlayerLogin(PlayerLoginEvent $event): void
    {
        $player = $event->getPlayer();
        if (!$player instanceof HCFPlayer) {
            return;
        }
        $player->load($this->core);
    }

    /**
     * @priority NORMAL
     * @param PlayerJoinEvent $event
     *
     */
    public function onPlayerJoin(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();
        $event->setJoinMessage("§7[§a+§7] §a{$player->getName()} §7entered the server!");
        if (!$player instanceof HCFPlayer) {
            return;
        }
        $pk = new GameRulesChangedPacket();
        $pk->gameRules = [
            "showcoordinates" => [
                1,
                true
            ]
        ];
        $player->sendDataPacket($pk);
        if (!$player->hasPlayedBefore()) {
            $player->teleport($player->getLevel()->getSpawnLocation());
        }
        $player->getBossBar()->spawn();
        $position = $player->asPosition();
        $folderName = $player->getLevel()->getFolderName();
        if ($folderName === "ender" || $folderName === "nether") {
            $player->teleport($player->getServer()->getDefaultLevel()->getSpawnLocation());
            $dimension = DimensionIds::NETHER;
            if ($position->getLevel()->getFolderName() === "ender") {
                $dimension = DimensionIds::THE_END;
            }
            $pk = new ChangeDimensionPacket();
            $pk->dimension = $dimension;
            $pk->respawn = false;
            $pk->position = $position;
            $player->dataPacket($pk);
            $player->sendPlayStatus(PlayStatusPacket::PLAYER_SPAWN);
            $player->teleport($position);
        }
        HCF::getInstance()->getScheduler()->scheduleDelayedTask(new class($player) extends Task {

            /** @var HCFPlayer */
            private $player;

            /**
             *  constructor.
             *
             * @param HCFPlayer $player
             */
            public function __construct(HCFPlayer $player)
            {
                $this->player = $player;
            }

            /**
             * @param int $currentTick
             */
            public function onRun(int $currentTick): void
            {
                if ($this->player->isOnline() === false) {
                    return;
                }
                $item = $this->player->getInventory()->getItemInHand();
                $this->player->getInventory()->setItemInHand(Item::get(Item::TOTEM));
                $pk = new LevelEventPacket();
                $pk->position = $this->player;
                $pk->evid = LevelEventPacket::EVENT_SOUND_TOTEM;
                $pk->data = 0;
                $this->player->sendDataPacket($pk);
                $pk = new LevelEventPacket;
                $pk->evid = LevelEventPacket::EVENT_ADD_PARTICLE_MASK | (Particle::TYPE_TOTEM & 0xFFF);
                $pk->position = $this->player;
                $pk->data = 0;
                $this->player->sendDataPacket($pk);
                $pk = new ActorEventPacket();
                $pk->entityRuntimeId = $this->player->getId();
                $pk->event = ActorEventPacket::CONSUME_TOTEM;
                $pk->data = 0;
                $this->player->sendDataPacket($pk);
                $this->player->sendTitle("  ", TextFormat::RESET . TextFormat::BOLD . TextFormat::BLUE . "Kings" . TextFormat::WHITE . "HCF\n" . TextFormat::RESET . TextFormat::GRAY . HCF::GAMEMODE . "\n\n\n\n\n\n\n", 5, 20, 5);
                $this->player->getInventory()->setItemInHand($item);
            }
        }, 40);
    }

    /**
     * @priority NORMAL
     * @param PlayerQuitEvent $event
     */
    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();
        $event->setQuitMessage("§7[§c-§7] §c{$player->getName()} §7left the server!");
        if (!$player instanceof HCFPlayer) {
            return;
        }
        $player->getBossBar()->despawn();
        $player->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, false);
        $player->setNameTagVisible(true);
        $player->setInvincible($player->getInvincibilityTime());
    }

    /**
     * @priority HIGHEST
     * @param PlayerCreationEvent $event
     */
    public function onPlayerCreation(PlayerCreationEvent $event): void
    {
        $event->setPlayerClass(HCFPlayer::class);
    }

    /**
     * @priority HIGH
     * @param PlayerCommandPreprocessEvent $event
     *
     */
    public function onPlayerCommandPreprocess(PlayerCommandPreprocessEvent $event): void
    {
        $player = $event->getPlayer();
        if (strpos($event->getMessage(), "/") !== 0) {
            return;
        }
        if (!isset($this->command[$player->getRawUniqueId()])) {
            $this->command[$player->getRawUniqueId()] = time();
            return;
        }
        if (time() - $this->command[$player->getRawUniqueId()] >= 3) {
            $this->command[$player->getRawUniqueId()] = time();
            return;
        }
        //$seconds = 3 - (time() - $this->command[$player->getRawUniqueId()]);
        /*$player->sendMessage(Translation::getMessage("actionCooldown", [
            "amount" => TextFormat::RED . $seconds
        ]));
        $event->setCancelled();*/
    }

    /**
     * @priority HIGH
     * @param PlayerChatEvent $event
     *
     * @throws TranslationException
     */
    public function onPlayerChat(PlayerChatEvent $event): void
    {
        $player = $event->getPlayer();
        if (!$player instanceof HCFPlayer) {
            return;
        }
        if ($player->getMuteTime() !== null && $player->getMuteEffector() !== null && $player->getMuteReason() !== null) {
            $time = $player->getMuteTime() - time();
            if ($time > 0) {
                $event->setCancelled();
                $days = floor($time / 86400);
                $hours = $hours = floor(($time / 3600) % 24);
                $minutes = floor(($time / 60) % 60);
                $seconds = $time % 60;
                $time = "$days days, $hours hours, $minutes minutes, $seconds seconds";
                $player->sendMessage(Translation::getMessage(
                    "muteMessage", [
                    "name" => $player->getMuteEffector(),
                    "reason" => $player->getMuteReason(),
                    "time" => $time
                ]));
                return;
            }

        }
        if (!isset($this->chat[$player->getRawUniqueId()])) {
            $this->chat[$player->getRawUniqueId()] = time();
            return;
        }
        if (time() - $this->chat[$player->getRawUniqueId()] >= 3) {
            $this->chat[$player->getRawUniqueId()] = time();
            return;
        }
        //$seconds = 3 - (time() - $this->chat[$player->getRawUniqueId()]);
        /*$player->sendMessage(Translation::getMessage("actionCooldown", [
            "amount" => TextFormat::RED . $seconds
        ]));
        $event->setCancelled();*/
    }

    /**
     * @priority HIGHEST
     * @param PlayerMoveEvent $event
     *
     * @throws TranslationException
     */
    public function onPlayerMove(PlayerMoveEvent $event): void
    {
        $player = $event->getPlayer();
        $x = abs($player->getFloorX());
        $y = abs($player->getFloorY());
        $z = abs($player->getFloorZ());
        $border = HCF::BORDER;
        $message = Translation::getMessage("borderReached");
        if ($x >= $border) {
            $player->teleport(new Vector3($border, $y, $z));
            $player->sendMessage($message);
        }
        if ($z >= $border) {
            $player->teleport(new Vector3($x, $y, $border));
            $player->sendMessage($message);
        }
        if ($x >= $border && abs($z) >= $border) {
            $player->teleport(new Vector3($border, $y, $border));
            $player->sendMessage($message);
        }
        $to = $event->getTo();
        if ($to->getY() < 0) {
            $pos = new Position($to->getX(), 0, $to->getZ(), $to->getLevel());
            $to->getLevel()->setBlock($pos, Block::get(Block::BEDROCK));
            $player->teleport(Position::fromObject($pos->add(0, 1, 0), $pos->getLevel()));
        }
    }

    /**
     * @priority NORMAL
     * @param PlayerItemHeldEvent $event
     */
    public function onPlayerItemHeld(PlayerItemHeldEvent $event): void
    {
        $player = $event->getPlayer();
        $item = $event->getItem();
        if ($item->getId() === Item::TNT) {
            $player->getInventory()->remove($item);
        }
    }

    /**
     * @param PlayerDeathEvent $event
     */
    public function onPlayerDeath(PlayerDeathEvent $event): void
    {
        $player = $event->getPlayer();
        if ($this->core->isEndOfTheWorld() === true) {
            $this->players[$player->getRawUniqueId()] = true;
            HCF::getInstance()->getScheduler()->scheduleDelayedTask(new class($player) extends Task {

                /** @var HCFPlayer */
                private $player;

                /**
                 *  constructor.
                 *
                 * @param HCFPlayer $player
                 */
                public function __construct(HCFPlayer $player)
                {
                    $this->player = $player;
                }

                /**
                 * @param int $currentTick
                 */
                public function onRun(int $currentTick): void
                {
                    if (!$this->player->isClosed()) {
                        $this->player->close(null, TextFormat::RED . "You've died in EOTW! You can't rejoin!");
                    }
                }
            }, 20);
        }
    }

    /**
     * @priority NORMAL
     * @param PlayerInteractEvent $event
     *
     * @throws TranslationException
     * @throws Exception
     */
    public function onPlayerInteract(PlayerInteractEvent $event): void
    {
        $item = $event->getItem();
        $block = $event->getBlock();
        /** @var HCFPlayer $player */
        $player = $event->getPlayer();
        if ($player->hasEffect(28)){
            $event->setCancelled();
            return;
        }
        if ($item->getId() === Item::EXPERIENCE_BOTTLE) {
            $player->getInventory()->setItemInHand($item->setCount($item->getCount() - 1));
            $player->addXp(random_int(2, 7));
        }
        $tile = $block->getLevel()->getTile($block);
        if ($tile instanceof Sign && (!$player->isSneaking()) && $tile->getLine(0) === TextFormat::DARK_GRAY . "[" . TextFormat::AQUA . TextFormat::BOLD . "Elevator" . TextFormat::RESET . TextFormat::DARK_GRAY . "]") {
            if ($tile->getLine(2) !== "" || $tile->getLine(3) !== "") {
                return;
            }
            $value = false;
            if ($tile->getLine(1) === "Up") {
                for ($y = $block->getFloorY() + 1; $y <= Level::Y_MAX; $y++) {
                    $block = $block->getLevel()->getBlock(new Vector3($block->getX(), $y, $block->getZ()));
                    if ($value === false && ($block->canPassThrough() === true || $block->getId() === Block::SIGN_POST || $block->getId() === Block::WALL_SIGN)) {
                        $value = true;
                        continue;
                    }
                    if ($value === true && ($block->canPassThrough() === true || $block->getId() === Block::SIGN_POST || $block->getId() === Block::WALL_SIGN)) {
                        $player->teleport(Position::fromObject($block->add(0.5, -1, 0.5), $block->getLevel()));
                        $player->getLevel()->addSound(new EndermanTeleportSound($player));
                        $player->sendMessage(Translation::getMessage("shiftToNotTeleport"));
                        return;
                    }

                    $value = false;
                }
                $player->sendMessage(Translation::getMessage("couldNotFindSafeLocation"));
            }
            if ($tile->getLine(1) === "Down") {
                for ($y = $block->getFloorY() - 1; $y >= 0; $y--) {
                    $block = $block->getLevel()->getBlock(new Vector3($block->getX(), $y, $block->getZ()));
                    if ($value === false && ($block->canPassThrough() === true || $block->getId() === Block::SIGN_POST || $block->getId() === Block::WALL_SIGN)) {
                        $value = true;
                        continue;
                    }
                    if ($value === true && ($block->canPassThrough() === true || $block->getId() === Block::SIGN_POST || $block->getId() === Block::WALL_SIGN)) {
                        $player->teleport(Position::fromObject($block->add(0.5, 0, 0.5), $block->getLevel()));
                        $player->getLevel()->addSound(new EndermanTeleportSound($player));
                        $player->sendMessage(Translation::getMessage("shiftToNotTeleport"));
                        return;
                    }

                    $value = false;
                }
                $player->sendMessage(Translation::getMessage("couldNotFindSafeLocation"));
            }
        }
    }

    /**
     * @priority HIGHEST
     * @param BlockBreakEvent $event
     *
     * @throws TranslationException
     */
    public function onBlockBreak(BlockBreakEvent $event): void
    {
        if ($event->isCancelled()) {
            return;
        }
        /** @var HCFPlayer $player */
        $player = $event->getPlayer();
        if ($player->hasEffect(28)){
            $event->setCancelled();
            return;
        }
        $block = $event->getBlock();
        $player->addXp($event->getXpDropAmount() * 1.5);
        $event->setXpDropAmount(0);
        if (($block->getId() === Block::DIAMOND_ORE) && !isset($this->blocks[(string)$block->asVector3()])) {
            $count = 0;
            for ($x = $block->getX() - 4; $x <= $block->getX() + 4; $x++) {
                for ($z = $block->getZ() - 4; $z <= $block->getZ() + 4; $z++) {
                    for ($y = $block->getY() - 4; $y <= $block->getY() + 4; $y++) {
                        if (($player->getLevel()->getBlockIdAt($x, $y, $z) === Block::DIAMOND_ORE) && !isset($this->blocks[(string)new Vector3($x, $y, $z)])) {
                            $this->blocks[(string)new Vector3($x, $y, $z)] = true;
                            ++$count;
                        }
                    }
                }
            }
            $player->getServer()->broadcastMessage(Translation::getMessage("diamondFound", [
                "name" => TextFormat::GREEN . $player->getName(),
                "amount" => TextFormat::AQUA . $count
            ]));
        }
    }

    /**
     * @param SignChangeEvent $event
     */
    public function onSignChange(SignChangeEvent $event): void
    {
        $lines = $event->getLines();
        if ($lines[0] === "[elevator]") {
            $lowerName = strtolower($lines[1]);
            if ($lowerName === "up" || $lowerName === "down") {
                if ($lines[2] === "" && $lines[3] === "") {
                    $event->setLine(0, TextFormat::DARK_GRAY . "[" . TextFormat::AQUA . TextFormat::BOLD . "Elevator" . TextFormat::RESET . TextFormat::DARK_GRAY . "]");
                    $event->setLine(1, ucfirst($lines[1]));
                }
            }
        }
    }

    /**
     * @priority NORMAL
     * @param EntityLevelChangeEvent $event
     */
    public function onEntityLevelChange(EntityLevelChangeEvent $event): void
    {
        $entity = $event->getEntity();
        if (!$entity instanceof HCFPlayer) {
            return;
        }
        foreach ($entity->getFloatingTexts() as $floatingText) {
            if ($floatingText->isInvisible() && $event->getTarget()->getName() === $floatingText->getLevel()->getName()) {
                $floatingText->spawn($entity);
                continue;
            }
            if ((!$floatingText->isInvisible()) && $event->getTarget()->getName() !== $floatingText->getLevel()->getName()) {
                $floatingText->despawn($entity);
                continue;
            }
        }
    }

    /**
     * @priority HIGHEST
     * @param EntityDamageEvent $event
     */
    public function onEntityDamageEvent(EntityDamageEvent $event): void
    {
        if ($event->isCancelled()) {
            return;
        }
        $entity = $event->getEntity();
        if (!$entity instanceof Living) {
            return;
        }
        if ($event instanceof EntityDamageByEntityEvent) {
            $damager = $event->getDamager();
            if (!$damager instanceof HCFPlayer) {
                return;
            }
        }
    }

    /**
     * @priority NORMAL
     * @param EntitySpawnEvent $event
     */
    public function onEntitySpawn(EntitySpawnEvent $event): void
    {
        $entity = $event->getEntity();
        if ($entity instanceof PrimedTNT || $entity instanceof ExperienceOrb) {
            $entity->flagForDespawn();
        }
        if ($entity instanceof ItemEntity) {
            $entities = $entity->getLevel()->getNearbyEntities($entity->getBoundingBox()->expandedCopy(5, 5, 5));
            if (empty($entities)) {
                return;
            }
            $originalItem = $entity->getItem();
            foreach ($entities as $e) {
                if ($e instanceof ItemEntity && $entity->getId() !== $e->getId()) {
                    $item = $e->getItem();
                    if ($item->equals($originalItem)) {
                        $e->flagForDespawn();
                        $entity->getItem()->setCount($originalItem->getCount() + $item->getCount());
                    }
                }
            }
        }
    }

    /**
     * @priority NORMAL
     * @param CraftItemEvent $event
     */
    public function onCraftItem(CraftItemEvent $event): void
    {
        foreach ($event->getInputs() as $input) {
            if ($input->getId() === Item::WOOD2 || ($input->getId() === Item::WOOD && $input->getDamage() > 0)) {
                $event->setCancelled();
                return;
            }
        }
        foreach ($event->getOutputs() as $output) {
            $outputId = $output->getId();
            if ($outputId === Item::TNT || $outputId === Item::FISHING_ROD) {
                $event->setCancelled();
            }
        }
    }

    /**
     * @priority LOWEST
     * @param ChunkLoadEvent $event
     */
    public function onChunkLoad(ChunkLoadEvent $event): void
    {
        $addOn = HCF::BORDER;
        $level = $this->core->getServer()->getDefaultLevel();
        $xLimit = abs($level->getSpawnLocation()->getFloorX() + $addOn) >> 4;
        $zLimit = abs($level->getSpawnLocation()->getFloorZ() + $addOn) >> 4;
        if (abs($event->getChunk()->getX() >> 4) > $xLimit) {
            $level->unloadChunk($event->getChunk()->getX(), $event->getChunk()->getZ());
        }
        if (abs($event->getChunk()->getZ() >> 4) > $zLimit) {
            $level->unloadChunk($event->getChunk()->getX(), $event->getChunk()->getZ());
        }
    }
}