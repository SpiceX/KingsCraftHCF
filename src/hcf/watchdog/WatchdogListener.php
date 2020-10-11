<?php

namespace hcf\watchdog;

use hcf\groups\GroupManager;
use hcf\HCF;
use hcf\HCFPlayer;
use hcf\network\packets\InventoryTransactionPacket;
use hcf\translation\Translation;
use hcf\translation\TranslationException;
use hcf\watchdog\task\ProxyCheckTask;
use PDO;
use pocketmine\block\Block;
use pocketmine\block\Door;
use pocketmine\block\Fallable;
use pocketmine\block\FenceGate;
use pocketmine\block\Ladder;
use pocketmine\block\Trapdoor;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

class WatchdogListener implements Listener
{

    /** @var HCF */
    private $core;

    /** @var string[] */
    private $keys = [
        ""
    ];

    /** @var int */
    private $count = 0;

    /** @var int[] */
    private $fenceGateInteracts = [];

    /** @var int */
    private $fenceGateTime = 0;

    /** @var int[] */
    private $phasingMessages = [];

    /** @var int */
    private $autoClickTime = 0;

    /**
     * WatchdogListener constructor.
     *
     * @param HCF $core
     */
    public function __construct(HCF $core)
    {
        $this->core = $core;
    }

    /**
     * @priority LOWEST
     * @param PlayerJoinEvent $event
     */
    public function onPlayerJoin(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();
        if (!$player instanceof HCFPlayer) {
            return;
        }
        $ipAddress = $player->getAddress();
        $uuid = $player->getRawUniqueId();
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("SELECT riskLevel FROM ipAddress WHERE ipAddress = :ipAddress AND uuid = :uuid");
        $stmt->bindParam(":ipAddress", $ipAddress);
        $stmt->bindParam(":uuid", $uuid);
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result = $row['result'];
            if ($result === null) {
                ++$this->count;
                if ($this->count > count($this->keys) - 1) {
                    $this->count = 0;
                }
                $key = $this->keys[$this->count++];
                $this->core->getServer()->getAsyncPool()->submitTaskToWorker(new ProxyCheckTask($player->getName(), $ipAddress, $key), 0);
                return;
            }
            if ($result === 1) {
                $player->close(null, TextFormat::RED . "A malicious ip swapper was detected!");
                return;
            }
        }
        $stmt->closeCursor();
    }

    /**
     * @priority LOWEST
     * @param PlayerMoveEvent $event
     *
     * @throws TranslationException
     */
    public function onPlayerMove(PlayerMoveEvent $event): void
    {
        $player = $event->getPlayer();
        if (!$player instanceof HCFPlayer) {
            return;
        }
        if ($player->isFrozen()) {
            $event->setCancelled();
            $player->sendMessage(Translation::getMessage("frozen", [
                "name" => "You are"
            ]));
        }
        if ($player->getGamemode() === Player::SPECTATOR) {
            return;
        }
        if ((!isset($this->phasingMessages[$player->getRawUniqueId()])) || $this->phasingMessages[$player->getRawUniqueId()] !== time()) {
            /** @var Block[] $blocks */
            $blocks = [];
            $position = new Position($event->getTo()->getX(), ceil($event->getTo()->getY()), $event->getTo()->getZ(), $player->getLevel());
            $blocks[] = $player->getLevel()->getBlock($position);
            $blocks[] = $player->getLevel()->getBlock(Position::fromObject($event->getTo()->add(0, 1, 0), $player->getLevel()));
            foreach ($blocks as $block) {
                if ($block->collidesWithBB($player->getBoundingBox()) === false) {
                    continue;
                }
                if ($block instanceof Fallable || $block instanceof Ladder || $block->canPassThrough() === true || ($block instanceof FenceGate && ($block->getDamage() & 0x04) > 0) || ($block instanceof Trapdoor && $block->getDamage() > 8)) {
                    continue;
                }
                if ($block instanceof Door) {
                    $damage = $block->getDamage();
                    $isUp = ($damage & 0x08) > 0;
                    if ($isUp) {
                        $down = $block->getSide(Vector3::SIDE_DOWN)->getDamage();
                        $up = $damage;
                    } else {
                        $down = $damage;
                        $up = $block->getSide(Vector3::SIDE_UP)->getDamage();
                    }
                    $isRight = ($up & 0x01) > 0;
                    $damage = $down & 0x07 | ($isUp ? 8 : 0) | ($isRight ? 0x10 : 0);
                    if (($damage & 0x04) > 0) {
                        continue;
                    }
                }
                $event->setCancelled();
                $player->knockBack($player, 0, $player->getX() - $position->getX(), $player->getZ() - $position->getZ(), 2);
                $player->setImmobile();
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
                        if ($this->player->isOnline()) {
                            $this->player->setImmobile(false);
                        }
                    }
                }, 20);
                foreach ($this->core->getServer()->getOnlinePlayers() as $onlinePlayer) {
                    /** @var HCFPlayer $onlinePlayer */
                    $groupIdentifier = $onlinePlayer->getGroup()->getIdentifier();
                    if ($groupIdentifier >= GroupManager::TRAINEE && $groupIdentifier <= GroupManager::OWNER) {
                        $onlinePlayer->sendMessage(TextFormat::DARK_RED . TextFormat::BOLD . " ▶ " . TextFormat::RESET . TextFormat::RED . "Watchdog alert! {$player->getName()} is in suspicion of phasing.");
                    }
                }
                $this->phasingMessages[$player->getRawUniqueId()] = time();
                return;
            }
        }
    }

    /**
     * @priority HIGHEST
     * @param PlayerInteractEvent $event
     *
     */
    public function onPlayerInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();
        if (!$player instanceof HCFPlayer) {
            return;
        }
        $block = $event->getBlock();
        if ($block instanceof FenceGate) {
            if (time() !== $this->fenceGateTime) {
                $this->fenceGateTime = time();
                $this->fenceGateInteracts = [];
            }
            if (!isset($this->fenceGateInteracts[$player->getRawUniqueId()])) {
                $this->fenceGateInteracts[$player->getRawUniqueId()] = 1;
            } else {
                ++$this->fenceGateInteracts[$player->getRawUniqueId()];
            }
            if ($this->fenceGateInteracts[$player->getRawUniqueId()] > 8) {
                $this->fenceGateInteracts[$player->getRawUniqueId()] = 0;
                foreach ($this->core->getServer()->getOnlinePlayers() as $onlinePlayer) {
                    /** @var HCFPlayer $onlinePlayer */
                    $groupIdentifier = $onlinePlayer->getGroup()->getIdentifier();
                    if ($groupIdentifier >= GroupManager::TRAINEE && $groupIdentifier <= GroupManager::OWNER) {
                        $onlinePlayer->sendMessage(TextFormat::DARK_RED . TextFormat::BOLD . " ▶ " . TextFormat::RESET . TextFormat::RED . "Watchdog alert! {$player->getName()} is in suspicion of block glitching into a base.");
                    }
                }
                $directionVector = $player->getDirectionVector();
                $player->knockBack($player, 0, -$directionVector->getX(), -$directionVector->getZ(), 0.5);
                $player->setImmobile();
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
                        if ($this->player->isOnline()) {
                            $this->player->setImmobile(false);
                        }
                    }
                }, 20);
            }
        }
    }

    /**
     * @priority NORMAL
     * @param PlayerQuitEvent $event
     *
     * @throws TranslationException
     */
    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();
        if (!$player instanceof HCFPlayer) {
            return;
        }
        if ($player->isFrozen()) {
            $uuid = $player->getRawUniqueId();
            $name = $player->getName();
            $effector = "Watchdog";
            $reason = "Leaving while being frozen";
            $punishTime = time();
            $expiration = $punishTime + 604800;
            $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("INSERT INTO bans(uuid, username, effector, reason, expiration) VALUES(:uuid, :name, :effector, :reason, :expiration);");
            $stmt->bindParam(":uuid", $uuid);
            $stmt->bindParam(":name", $name);
            $stmt->bindParam(":effector", $effector);
            $stmt->bindParam(":reason", $reason);
            $stmt->bindParam(":expiration", $expiration);
            $stmt->execute();
            $stmt->closeCursor();
            $time = 604800;
            $days = floor($time / 86400);
            $hours = floor(($time / 3600) % 24);
            $minutes = floor(($time / 60) % 60);
            $seconds = $time % 60;
            $time = "$days days, $hours hours, $minutes minutes, $seconds seconds";
            $this->core->getServer()->broadcastMessage(Translation::getMessage("banBroadcast", [
                "name" => $player->getName(),
                "effector" => $effector,
                "time" => $time,
                "reason" => $reason
            ]));
            $player->close(null, Translation::getMessage("banMessage", [
                "name" => $effector,
                "reason" => $reason,
                "time" => $time
            ]));
        }
    }

    /**
     * @priority LOWEST
     * @param PlayerCommandPreprocessEvent $event
     *
     * @throws TranslationException
     */
    public function onPlayerCommandPreprocess(PlayerCommandPreprocessEvent $event): void
    {
        $player = $event->getPlayer();
        if (!$player instanceof HCFPlayer) {
            return;
        }
        if ($player->isFrozen()) {
            $message = $event->getMessage();
            $value = false;
            $commands = ["/msg", "/w", "/tell", "/whisper", "/message", "/pm", "/m"];
            foreach ($commands as $command) {
                if (strpos($message, $command) !== false) {
                    $value = true;
                }
            }
            if ($value === true) {
                $player->sendMessage(Translation::getMessage("frozen", [
                    "name" => "You are"
                ]));
            }
        }
    }

    /**
     * @priority LOWEST
     * @param EntityDamageEvent $event
     *
     * @throws TranslationException
     */
    public function onEntityDamage(EntityDamageEvent $event): void
    {
        $entity = $event->getEntity();
        if (!$entity instanceof HCFPlayer) {
            return;
        }
        if ($entity->isFrozen()) {
            $event->setCancelled();
            $entity->sendMessage(Translation::getMessage("frozen", [
                "name" => "You are"
            ]));
            if ($event instanceof EntityDamageByEntityEvent) {
                $damager = $event->getDamager();
                if (!$damager instanceof HCFPlayer) {
                    return;
                }
                $damager->sendMessage(Translation::getMessage("frozen", [
                    "name" => $entity->getName() . " is"
                ]));
            }
        }
    }

    /**
     * @priority NORMAL
     * @param DataPacketReceiveEvent $event
     */
    public function onDataPacketReceive(DataPacketReceiveEvent $event): void
    {
        $packet = $event->getPacket();
        $player = $event->getPlayer();
        if (!$player instanceof HCFPlayer) {
            return;
        }
        if (($packet instanceof InventoryTransactionPacket) && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) {
            ++$player->cps;
            if ($this->autoClickTime !== time()) {
                $staffs = [];
                foreach ($this->core->getServer()->getOnlinePlayers() as $onlinePlayer) {
                    /** @var HCFPlayer $onlinePlayer */
                    $groupIdentifier = $onlinePlayer->getGroup()->getIdentifier();
                    if ($groupIdentifier >= GroupManager::TRAINEE && $groupIdentifier <= GroupManager::OWNER) {
                        $staffs[] = $onlinePlayer;
                    }
                }
                foreach ($this->core->getServer()->getOnlinePlayers() as $player) {
                    if ($player instanceof HCFPlayer) {
                        if ($player->cps > 30) {
                            foreach ($staffs as $staff) {
                                $staff->sendMessage(TextFormat::DARK_RED . TextFormat::BOLD . " ▶ " . TextFormat::RESET . TextFormat::RED . "Watchdog alert! {$player->getName()} is in suspicion of auto-clicking. Current cps is: $player->cps");
                            }
                        }
                        $player->cps = 0;
                    }
                }
                $this->autoClickTime = time();
            }
        }
    }
}
