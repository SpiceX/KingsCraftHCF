<?php

namespace hcf\combat;

use hcf\combat\entity\LogoutVillager;
use hcf\groups\Group;
use hcf\HCF;
use hcf\HCFPlayer;
use hcf\item\AntiTrapper;
use hcf\item\CustomItem;
use hcf\item\LumberAxe;
use hcf\kit\task\SetClassTask;
use hcf\task\SpecialItemCooldown;
use hcf\translation\Translation;
use hcf\translation\TranslationException;
use hcf\util\Padding;
use pocketmine\block\Slab;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\EnderPearl;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

class CombatListener implements Listener
{

    /** @var HCF */
    private $core;

    /** @var int[] */
    private $godAppleCooldowns = [];

    /** @var int[] */
    private $goldenAppleCooldown = [];

    /**
     * CombatListener constructor.
     *
     * @param HCF $core
     */
    public function __construct(HCF $core)
    {
        $this->core = $core;
    }

    /**
     * @priority NORMAL
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
        if ($player->getGroup()->getIdentifier() >= Group::TRAINEE && $player->getGroup()->getIdentifier() <= Group::OWNER) {
            return;
        }
        if (strpos($event->getMessage(), "/") !== 0) {
            return;
        }
        if ($player->isTagged()) {
            $player->sendMessage(Translation::getMessage("noPermissionCombatTag"));
            $event->setCancelled();
        }
    }

    /**
     * @priority LOW
     * @param PlayerItemConsumeEvent $event
     *
     * @throws TranslationException
     */
    public function onPlayerItemConsume(PlayerItemConsumeEvent $event): void
    {
        $player = $event->getPlayer();
        $item = $event->getItem();
        if ($item->getId() === Item::ENCHANTED_GOLDEN_APPLE) {
            if (isset($this->godAppleCooldowns[$player->getRawUniqueId()])) {
                if ((time() - $this->godAppleCooldowns[$player->getRawUniqueId()]) < 10800) {
                    if (!$event->isCancelled()) {
                        $time = 10800 - (time() - $this->godAppleCooldowns[$player->getRawUniqueId()]);
                        $hours = floor($time / 3600);
                        $minutes = floor(($time / 60) % 60);
                        $seconds = $time % 60;
                        $time = "$hours hours, $minutes minutes, $seconds seconds";
                        $player->sendPopup(Translation::getMessage("godAppleCooldown", [
                            "time" => $time
                        ]));
                        $event->setCancelled();
                        return;
                    }
                }
                $this->godAppleCooldowns[$player->getRawUniqueId()] = time();
                return;
            }
            $this->godAppleCooldowns[$player->getRawUniqueId()] = time();
            return;
        }
        if ($item->getId() === Item::GOLDEN_APPLE) {
            if (isset($this->goldenAppleCooldown[$player->getRawUniqueId()])) {
                if ((time() - $this->goldenAppleCooldown[$player->getRawUniqueId()]) < 10) {
                    if (!$event->isCancelled()) {
                        $time = 10 - (time() - $this->goldenAppleCooldown[$player->getRawUniqueId()]);
                        $player->sendPopup(Translation::getMessage("actionCooldown", [
                            "amount" => TextFormat::RED . $time
                        ]));
                        $event->setCancelled();
                        return;
                    }
                }
                $this->goldenAppleCooldown[$player->getRawUniqueId()] = time();
                return;
            }
            $this->goldenAppleCooldown[$player->getRawUniqueId()] = time();
            return;
        }
    }

    /**
     * @priority NORMAL
     * @param PlayerRespawnEvent $event
     */
    public function onPlayerRespawn(PlayerRespawnEvent $event): void
    {
        $player = $event->getPlayer();
        $level = $player->getServer()->getDefaultLevel();
        $spawn = $level->getSpawnLocation();
        if (!$player instanceof HCFPlayer) {
            return;
        }
        $this->core->getScheduler()->scheduleDelayedTask(new class($player, $spawn) extends Task {

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
                if (!$this->player->isClosed()) {
                    $this->player->teleport($this->position);
                }
            }
        }, 1);
    }

    /**
     * @priority NORMAL
     * @param PlayerDeathEvent $event
     *
     * @throws TranslationException
     */
    public function onPlayerDeath(PlayerDeathEvent $event): void
    {
        $player = $event->getPlayer();
        if (!$player instanceof HCFPlayer) {
            return;
        }
        $cause = $player->getLastDamageCause();
        $message = Translation::getMessage("death", [
            "name" => TextFormat::GREEN . $player->getName() . TextFormat::DARK_GRAY . "[" . TextFormat::DARK_RED . TextFormat::BOLD . $player->getKills() . TextFormat::RESET . TextFormat::DARK_GRAY . "]",
        ]);
        if ($cause instanceof EntityDamageByEntityEvent) {
            $killer = $cause->getDamager();
            if ($killer instanceof HCFPlayer) {
                $killer->addKills();
                $message = Translation::getMessage("deathByPlayer", [
                    "name" => TextFormat::DARK_RED . $player->getName() . TextFormat::DARK_GRAY . "[" . TextFormat::RED . $player->getKills() . TextFormat::RESET . TextFormat::DARK_GRAY . "]",
                    "killer" => TextFormat::RED . $killer->getName() . TextFormat::DARK_GRAY . "[" . TextFormat::YELLOW . $killer->getKills() . TextFormat::RESET . TextFormat::DARK_GRAY . "]",
                    "item" => $killer->getInventory()->getItemInHand()->getId() === Item::AIR ? '§3the hand' : $killer->getInventory()->getItemInHand()->getCustomName()
                ]);
            }
        }
        $player->combatTag(false);
        $player->setInvincible(200);
        $event->setDeathMessage($message);
        $deathBanTime = $player->getGroup()->getDeathBanTime();
        if ($deathBanTime > 0) {
            if ($player->getLives() <= 0) {
                $time = time();
                $uuid = $player->getRawUniqueId();
                $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("UPDATE players SET deathBanTime = ? WHERE uuid = ?");
                $stmt->bind_param("is", $time, $uuid);
                $stmt->execute();
                $stmt->close();
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
                            $deathBanTime = $this->player->getGroup()->getDeathBanTime();
                            $days = floor($deathBanTime / 86400);
                            $hours = floor(($deathBanTime / 3600) % 24);
                            $minutes = floor(($deathBanTime / 60) % 60);
                            $seconds = $deathBanTime % 60;
                            $this->player->close(null, Translation::getMessage("banMessage", [
                                "name" => "Operator",
                                "reason" => "Death ban",
                                "time" => "$days days, $hours hours, $minutes minutes, $seconds seconds"
                            ]));
                        }
                    }
                }, 20);
                return;
            }
            $player->removeLife();
            $player->sendMessage(Translation::getMessage("lives", [
                "amount" => TextFormat::GREEN . $player->getLives()
            ]));
            $this->core->getScheduler()->scheduleTask(new SetClassTask($player));
        }
    }

    /**
     * @priority NORMAL
     * @param PlayerMoveEvent $event
     *
     * @throws TranslationException
     */
    public function onPlayerMove(PlayerMoveEvent $event): void
    {
        $to = $event->getTo();
        $areas = $this->core->getAreaManager()->getAreasInPosition($to);
        $player = $event->getPlayer();
        if (!$player instanceof HCFPlayer) {
            return;
        }
        if ($player->isInvincible() && $this->core->isStartOfTheWorld() === false) {
            $claim = $this->core->getFactionManager()->getClaimInPosition($to);
            if ($claim !== null) {
                $event->setCancelled();
                $player->sendMessage(Translation::getMessage("mayNotEnterWhilePvpTimerOn"));
                return;
            }
        }
        if (!$player->isTagged()) {
            return;
        }
        if ($areas === null) {
            return;
        }
        $pvp = true;
        foreach ($areas as $area) {
            if ($pvp === true && ($area->getPvpFlag() === false)) {
                $pvp = false;
            }
        }
        if ($pvp === false) {
            $event->setCancelled();
            $player->sendMessage(Translation::getMessage("enterSafeZoneInCombat"));
        }
    }

    /**
     * @priority NORMAL
     * @param PlayerQuitEvent $event
     */
    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();
        if (!$player instanceof HCFPlayer) {
            return;
        }
        if ($player->isTagged() || $player->canLogout() === false) {
            $nbt = Entity::createBaseNBT($player->asPosition());
            $villager = new LogoutVillager($player->getLevel(), $nbt);
            $villager->setPlayer($player);
            $player->getLevel()->addEntity($villager);
            $villager->spawnToAll();
        }
    }

    /**
     * @priority HIGH
     * @param PlayerInteractEvent $event
     */
    public function onPlayerInteract(PlayerInteractEvent $event): void
    {
        if ($event->isCancelled()) {
            return;
        }
        $player = $event->getPlayer();
        if (!$player instanceof HCFPlayer) {
            return;
        }
        $item = $event->getItem();
        if ($item->getId() === Item::ENDER_PEARL) {
            if ($item->getDamage() === 1) {
                if (time() - $player->getEnderPearlTime() < 10) {
                    $event->setCancelled();
                    return;
                }
                $player->setEnderPearlTime(time() - 2);
                return;
            }
            if (time() - $player->getEnderPearlTime() < 10) {
                $event->setCancelled();
                return;
            }
            $player->setEnderPearlTime(time() - 2);
            return;
        }
    }

    public function onEntityDamageByEntity(EntityDamageByEntityEvent $event): void
    {
        $damager = $event->getDamager();
        $victim = $event->getEntity();
        if (!$damager instanceof HCFPlayer || !$victim instanceof Player) {
            return;
        }
        $item = $damager->getInventory()->getItemInHand();
        $tag = $item->getNamedTagEntry(CustomItem::CUSTOM);
        if ($tag === null) {
            return;
        }
        if ($tag instanceof CompoundTag) {
            if ($item->getId() === Item::IRON_AXE && $damager->getLevel()->getFolderName() === "wild") {
                if ($item->getNamedTag()->hasTag("SpecialFeature")){
                    if ($damager->hasLumberAxeCooldown){
                        return;
                    }
                    $count = $tag->getInt(LumberAxe::HIT_COUNT);
                    ++$count;
                    $tag->setInt(LumberAxe::HIT_COUNT, $count);
                    if ($count >= 3) {
                        $victim->getArmorInventory()->setHelmet(Item::get(Item::AIR));
                        $tag->setInt(LumberAxe::HIT_COUNT, 0);
                        HCF::getInstance()->getScheduler()->scheduleRepeatingTask(new SpecialItemCooldown($damager, "LumberAxe"), 20);
                    }
                }
            }
            if ($item->getId() === Item::BONE && $damager->getLevel()->getFolderName() === "wild") {
                if ($damager->hasAntiTrapperCooldown) {
                    $damager->sendMessage("§cYour AntiTrapper is on cooldown.");
                    return;
                }
                $count = $tag->getInt(AntiTrapper::HIT_COUNT);
                $firstHit = $tag->getInt(AntiTrapper::FIRST_HIT_TIME);
                $uses = $tag->getInt(AntiTrapper::USES);

                if ($uses === 0) {
                    $damager->getInventory()->setItemInHand(Item::get(Item::AIR));
                } else {
                    ++$count;
                    $tag->setInt(AntiTrapper::HIT_COUNT, $count);
                    if ($firstHit === 0) {
                        $tag->setInt(AntiTrapper::FIRST_HIT_TIME, time());
                    }
                    $tag->setInt(AntiTrapper::LAST_HIT_TIME, time());
                    if ($count >= 3) {
                        $tag->setInt(AntiTrapper::USES, --$uses);
                        $tag->setInt(AntiTrapper::FIRST_HIT_TIME, 0);
                        $tag->setInt(AntiTrapper::HIT_COUNT, 0);
                        $damager->sendMessage("§6You have trapped your opponent!");
                        $this->core->getScheduler()->scheduleRepeatingTask(new SpecialItemCooldown($damager, 'AntiTrapper'), 20);
                        $victim->addEffect(new EffectInstance(Effect::getEffect(28), 300));
                        $victim->sendTitle("§cTrapped");
                        $victim->sendMessage("§c> Trapped, §7You will not be able to place, break or open blocks for 15 seconds.");
                    }
                    $tag->setInt(AntiTrapper::USES, $uses);
                    $lore = [];
                    $lore[] = "";
                    $lore[] = TextFormat::RESET . TextFormat::GRAY . "Hit a player 3 times in a row with the bone and he won’t be able to place,";
                    $lore[] = TextFormat::RESET . TextFormat::GRAY . "break, or open any blocks for a total time of 15 seconds.";
                    $lore[] = "";
                    $lore[] = TextFormat::RESET . TextFormat::AQUA . "AntiTrapper Uses: " . TextFormat::WHITE . $uses;
                    $item->setLore($lore);
                    $damager->getInventory()->setItemInHand($item);
                }


            }
        }
    }

    /**
     * @priority HIGHEST
     * @param EntityDamageEvent $event
     *
     * @throws TranslationException
     */
    public function onEntityDamage(EntityDamageEvent $event): void
    {
        if ($event->isCancelled()) {
            return;
        }
        $entity = $event->getEntity();
        if ($event instanceof EntityDamageByEntityEvent) {
            $damager = $event->getDamager();
            if ($damager instanceof HCFPlayer) {
                $damager->getBossBar()->update(Padding::centerText(
                    $this->core->getConfig()->get('bossbar_title', "§l§b» §9Kings§fHCF §b«" . "\n\n§r") .
                    "§fName: §9" . $damager->getName() . "  " .
                    "§fCPS: §9" . $this->core->getCpsCounter()->getCps($damager) . "  " .
                    "§fPing: §9" . $damager->getPing() . "  " .
                    "§fVictim: §9" . $entity->getNameTag()
                ), 1
                );
            }

        }
        if ($entity instanceof HCFPlayer) {
            if ($entity->isInvincible() || $this->core->isStartOfTheWorld()) {
                $event->setCancelled();
            }
            if ($event instanceof EntityDamageByEntityEvent) {
                $damager = $event->getDamager();
                if (!$damager instanceof HCFPlayer) {
                    return;
                }

                if ($damager->isInvincible()) {
                    $damager->sendMessage(Translation::getMessage("attackWhileInvincible", [
                        "name" => $entity->getName()
                    ]));
                    $event->setCancelled();
                    return;
                }
                if ($entity->isInvincible()) {
                    $damager->sendMessage(Translation::getMessage("isInvincible", [
                        "name" => $entity->getName()
                    ]));
                    return;
                }
                $damager->combatTag();
                $entity->combatTag();
            }
        }
    }

    /**
     * @priority HIGH
     * @param EntityTeleportEvent $event
     *
     * @throws TranslationException
     */
    public function onEntityTeleport(EntityTeleportEvent $event): void
    {
        $to = $event->getTo();
        $areas = $this->core->getAreaManager()->getAreasInPosition($to);
        $entity = $event->getEntity();
        if (!$entity instanceof HCFPlayer) {
            return;
        }
        if (!$entity->isTagged()) {
            return;
        }
        if ($areas === null) {
            return;
        }
        $pvp = true;
        foreach ($areas as $area) {
            if ($pvp === true && ($area->getPvpFlag() === false)) {
                $pvp = false;
            }
        }
        if ($pvp === false) {
            $event->setCancelled();
            $entity->sendMessage(Translation::getMessage("enterSafeZoneInCombat"));
        }
    }

    /**
     * @priority NORMAL
     * @param ProjectileHitBlockEvent $event
     */
    public function onProjectileHitBlock(ProjectileHitBlockEvent $event): void
    {
        if ($event->getBlockHit() instanceof Slab) {
            $entity = $event->getEntity();
            if ($entity instanceof EnderPearl) {
                $player = $entity->getOwningEntity();
                if (!$player instanceof HCFPlayer) {
                    return;
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
                        $directionVector = $this->player->getDirectionVector()->multiply(2);
                        $position = Position::fromObject($this->player->add($directionVector->getX(), 0, $directionVector->getZ()), $this->player->getLevel());
                        if ($this->player->isOnline()) {
                            $this->player->teleport($position);
                        }
                    }
                }, 5);
            }
        }
    }
}