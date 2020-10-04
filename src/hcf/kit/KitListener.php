<?php

namespace hcf\kit;

use hcf\HCF;
use hcf\HCFPlayer;
use hcf\kit\task\ResetEffectsTask;
use hcf\kit\task\SetClassTask;
use hcf\translation\Translation;
use hcf\translation\TranslationException;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\level\sound\AnvilBreakSound;
use pocketmine\level\sound\BlazeShootSound;
use pocketmine\utils\TextFormat;

class KitListener implements Listener {

    /** @var HCF */
    private $core;

    /**
     * KitListener constructor.
     *
     * @param HCF $core
     */
    public function __construct(HCF $core) {
        $this->core = $core;
    }

    /**
     * @priority NORMAL
     * @param PlayerJoinEvent $event
     */
    public function onPlayerJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof HCFPlayer) {
            return;
        }
        $player->removeAllEffects();
        $this->core->getScheduler()->scheduleDelayedTask(new SetClassTask($player), 1);
    }

    /**
     * @priority HIGHEST
     * @param PlayerMoveEvent $event
     *
     * @throws TranslationException
     */
    public function onPlayerMove(PlayerMoveEvent $event): void {
        if($event->isCancelled()) {
            return;
        }
        $player = $event->getPlayer();
        if(!$player instanceof HCFPlayer) {
            return;
        }
        if($player->getClass() === HCFPlayer::MINER) {
            if($player->getY() > 16) {
                if($player->hasEffect(Effect::INVISIBILITY)) {
                    $player->removeEffect(Effect::INVISIBILITY);
                    $player->sendMessage(Translation::getMessage("visibilityChange", [
                        "mode" => TextFormat::YELLOW . "visible"
                    ]));
                }
                return;
            }
            if($player->hasEffect(Effect::INVISIBILITY)) {
                return;
            }
            $player->addEffect(new EffectInstance(Effect::getEffect(Effect::INVISIBILITY), 999999999, 0));
            $player->sendMessage(Translation::getMessage("visibilityChange", [
                "mode" => TextFormat::YELLOW . "invisible"
            ]));
            return;
        }
    }

    /**
     * @priority HIGHEST
     * @param PlayerInteractEvent $event
     *
     */
    public function onPlayerInteract(PlayerInteractEvent $event): void {
        if($event->isCancelled()) {
            return;
        }
        $player = $event->getPlayer();
        if(!$player instanceof HCFPlayer) {
            return;
        }
        $class = $player->getClass();
        if($class !== null && $class !== HCFPlayer::MINER) {
            $item = $player->getInventory()->getItemInHand();
            if($class === HCFPlayer::ARCHER) {
                switch($item->getId()) {
                    case Item::SUGAR:
                        $time = 30 - (time() - $player->getBuffDelayTime());
                        if($time > 0) {
                            return;
                        }
                        $player->setBuffDelayTime();
                        $player->getLevel()->addSound(new BlazeShootSound($player));
                        $player->getInventory()->setItemInHand($item->setCount($item->getCount() - 1));
                        $player->addEffect(new EffectInstance(Effect::getEffect(Effect::SPEED), 200, 3));
                        $this->core->getScheduler()->scheduleDelayedTask(new ResetEffectsTask($player), 200);
                        break;
                    default:
                        return;
                }
            }
            if($player->getClass() === HCFPlayer::ROGUE) {
                switch($item->getId()) {
                    case Item::SUGAR:
                        $time = 30 - (time() - $player->getBuffDelayTime());
                        if($time > 0) {
                            return;
                        }
                        $player->setBuffDelayTime();
                        $player->getLevel()->addSound(new BlazeShootSound($player));
                        $player->getInventory()->setItemInHand($item->setCount($item->getCount() - 1));
                        $player->addEffect(new EffectInstance(Effect::getEffect(Effect::SPEED), 200, 4));
                        $this->core->getScheduler()->scheduleDelayedTask(new ResetEffectsTask($player), 200);
                        break;
                    default:
                        return;
                }
            }
            if($player->getClass() === HCFPlayer::BARD) {
                switch($item->getId()) {
                    case Item::SUGAR:
                        if($player->getFaction() !== null) {
                            $energy = ($player->getBardEnergy() - 40);
                            if($energy > 0) {
                                $player->setBardEnergy($energy);
                            }
                            else {
                                return;
                            }
                            $onlineMembers = $player->getFaction()->getOnlineMembers();
                            foreach($onlineMembers as $member) {
                                if($member->hasEffect(Effect::SPEED) && $member->getEffect(Effect::SPEED)->getAmplifier() === 2) {
                                    continue;
                                }
                                if($member->distance($player) > 20) {
                                    continue;
                                }
                                $member->addEffect(new EffectInstance(Effect::getEffect(Effect::SPEED), 100, 2));
                                $this->core->getScheduler()->scheduleDelayedTask(new ResetEffectsTask($member), 100);
                            }
                            $player->getLevel()->addSound(new BlazeShootSound($player));
                            $player->getInventory()->setItemInHand($item->setCount($item->getCount() - 1));
                        }
                        break;
                    case Item::BLAZE_POWDER:
                        if($player->getFaction() !== null) {
                            $energy = ($player->getBardEnergy() - 50);
                            if($energy > 0) {
                                $player->setBardEnergy($energy);
                            }
                            else {
                                return;
                            }
                            $onlineMembers = $player->getFaction()->getOnlineMembers();
                            foreach($onlineMembers as $member) {
                                if($member->hasEffect(Effect::STRENGTH)) {
                                    if($member->getEffect(Effect::STRENGTH)->getAmplifier() === 1) {
                                        continue;
                                    }
                                }
                                if($member->distance($player) > 20) {
                                    continue;
                                }
                                $member->addEffect(new EffectInstance(Effect::getEffect(Effect::STRENGTH), 100, 1));
                                $this->core->getScheduler()->scheduleDelayedTask(new ResetEffectsTask($member), 100);
                            }
                            $player->getLevel()->addSound(new BlazeShootSound($player));
                            $player->getInventory()->setItemInHand($item->setCount($item->getCount() - 1));
                        }
                        break;
                    case Item::IRON_INGOT:
                        if($player->getFaction() !== null) {
                            $energy = ($player->getBardEnergy() - 20);
                            if($energy > 0) {
                                $player->setBardEnergy($energy);
                            }
                            else {
                                return;
                            }
                            $onlineMembers = $player->getFaction()->getOnlineMembers();
                            foreach($onlineMembers as $member) {
                                if($member->hasEffect(Effect::RESISTANCE) && $member->getEffect(Effect::RESISTANCE)->getAmplifier() === 3) {
                                    continue;
                                }
                                if($member->distance($player) > 20) {
                                    continue;
                                }
                                $member->addEffect(new EffectInstance(Effect::getEffect(Effect::RESISTANCE), 100, 3));
                                $this->core->getScheduler()->scheduleDelayedTask(new ResetEffectsTask($member), 100);
                            }
                            $player->getLevel()->addSound(new BlazeShootSound($player));
                            $player->getInventory()->setItemInHand($item->setCount($item->getCount() - 1));
                        }
                        break;
                    case Item::FEATHER:
                        if($player->getFaction() !== null) {
                            $energy = ($player->getBardEnergy() - 20);
                            if($energy > 0) {
                                $player->setBardEnergy($energy);
                            }
                            else {
                                return;
                            }
                            $onlineMembers = $player->getFaction()->getOnlineMembers();
                            foreach($onlineMembers as $member) {
                                if($member->hasEffect(Effect::JUMP) && $member->getEffect(Effect::JUMP)->getAmplifier() === 6) {
                                    continue;
                                }
                                if($member->distance($player) > 20) {
                                    continue;
                                }
                                $member->addEffect(new EffectInstance(Effect::getEffect(Effect::JUMP), 100, 6));
                                $this->core->getScheduler()->scheduleDelayedTask(new ResetEffectsTask($member), 100);
                            }
                            $player->getLevel()->addSound(new BlazeShootSound($player));
                            $player->getInventory()->setItemInHand($item->setCount($item->getCount() - 1));
                        }
                        break;
                    case Item::GHAST_TEAR:
                        if($player->getFaction() !== null) {
                            $energy = ($player->getBardEnergy() - 20);
                            if($energy > 0) {
                                $player->setBardEnergy($energy);
                            }
                            else {
                                return;
                            }
                            $onlineMembers = $player->getFaction()->getOnlineMembers();
                            foreach($onlineMembers as $member) {
                                if($member->hasEffect(Effect::REGENERATION)) {
                                    if($member->getEffect(Effect::REGENERATION)->getAmplifier() === 2) {
                                        continue;
                                    }
                                }
                                if($member->distance($player) > 20) {
                                    continue;
                                }
                                $member->addEffect(new EffectInstance(Effect::getEffect(Effect::REGENERATION), 100, 2));
                                $this->core->getScheduler()->scheduleDelayedTask(new ResetEffectsTask($member), 100);
                            }
                            $player->getLevel()->addSound(new BlazeShootSound($player));
                            $player->getInventory()->setItemInHand($item->setCount($item->getCount() - 1));
                        }
                        break;
                    case Item::SPIDER_EYE:
                        $claim = $player->getCore()->getFactionManager()->getClaimInPosition($player);
                        if($claim === null) {
                            return;
                        }
                        if($claim->getFaction()->isInFaction($player)) {
                            return;
                        }
                        $onlineMembers = $claim->getFaction()->getOnlineMembers();
                        if(count($claim->getFaction()->getOnlineMembers()) === 0) {
                            return;
                        }
                        foreach($onlineMembers as $member) {
                            $member->addEffect(new EffectInstance(Effect::getEffect(Effect::WITHER), 100, 1));
                        }
                        $player->getLevel()->addSound(new BlazeShootSound($player));
                        $player->getInventory()->setItemInHand($item->setCount($item->getCount() - 1));
                        break;
                    case Item::MAGMA_CREAM:
                        if($player->getFaction() !== null) {
                            $onlineMembers = $player->getFaction()->getOnlineMembers();
                            foreach($onlineMembers as $member) {
                                if($member->hasEffect(Effect::FIRE_RESISTANCE)) {
                                    continue;
                                }
                                if($member->distance($player) > 20) {
                                    continue;
                                }
                                $member->addEffect(new EffectInstance(Effect::getEffect(Effect::FIRE_RESISTANCE), 600, 0));
                                $this->core->getScheduler()->scheduleDelayedTask(new ResetEffectsTask($member), 600);
                            }
                            $player->getLevel()->addSound(new BlazeShootSound($player));
                            $player->getInventory()->setItemInHand($item->setCount($item->getCount() - 1));
                            if($player->hasEffect(Effect::FIRE_RESISTANCE)) {
                                return;
                            }
                            $player->addEffect(new EffectInstance(Effect::getEffect(Effect::FIRE_RESISTANCE), 600, 0));
                            $this->core->getScheduler()->scheduleDelayedTask(new ResetEffectsTask($player), 600);
                        }
                        break;
                    default:
                        break;
                }
            }
        }
    }

    /**
     * @priority HIGH
     * @param BlockBreakEvent $event
     */
    public function onBlockBreak(BlockBreakEvent $event): void {
        if($event->isCancelled()) {
            return;
        }
        $player = $event->getPlayer();
        if(!$player instanceof HCFPlayer) {
            return;
        }
        if($player->getClass() === HCFPlayer::MINER) {
            foreach($event->getDrops() as $drop) {
                $player->getInventory()->addItem($drop);
            }
            $event->setDrops([]);
        }
    }

    /**
     * @priority HIGHEST
     * @param EntityDamageEvent $event
     */
    public function onEntityDamage(EntityDamageEvent $event): void {
        if($event->isCancelled()) {
            return;
        }
        if(!$event instanceof EntityDamageByEntityEvent) {
            return;
        }
        $damager = $event->getDamager();
        if(!$damager instanceof HCFPlayer) {
            return;
        }
        $entity = $event->getEntity();
        if(!$entity instanceof HCFPlayer) {
            return;
        }
        if($damager->getClass() === HCFPlayer::ROGUE) {
            if($damager->getInventory()->getItemInHand()->getId() === Item::GOLD_SWORD) {
                $damager->getLevel()->addSound(new AnvilBreakSound($damager));
                $damager->getInventory()->setItemInHand(Item::get(Item::AIR));
                $entity->setHealth(max(0, $entity->getHealth() - 8));
                $entity->sendTitle(TextFormat::RED . TextFormat::BOLD . "Backstabbed!");
            }
        }
        if($damager->getClass() === HCFPlayer::ARCHER) {
            if($event->getCause() === EntityDamageByEntityEvent::CAUSE_PROJECTILE) {
                $event->setBaseDamage($event->getBaseDamage() * 1.25);
            }
        }
    }

    /**
     * @priority HIGHEST
     * @param ProjectileHitEntityEvent $event
     */
    public function onProjectileHitEntity(ProjectileHitEntityEvent $event): void {
        $entity = $event->getEntityHit();
        if(!$entity instanceof HCFPlayer) {
            return;
        }
        $damager = $event->getEntity()->getOwningEntity();
        if(!$damager instanceof HCFPlayer) {
            return;
        }
        if($damager->getClass() === HCFPlayer::ARCHER) {
            if($damager->getArcherTagPlayer() === $entity) {
                return;
            }
            $damager->archerTag($entity);
        }
    }

    /**
     * @priority HIGHEST
     * @param InventoryTransactionEvent $event
     */
    public function onInventoryTransaction(InventoryTransactionEvent $event): void
    {
        $transaction = $event->getTransaction();
        foreach($transaction->getActions() as $action) {
            if($action instanceof SlotChangeAction) {
                $inventory = $action->getInventory();
                if($inventory instanceof ArmorInventory) {
                    $holder = $inventory->getHolder();
                    if($holder instanceof HCFPlayer) {
                        $this->core->getScheduler()->scheduleDelayedTask(new SetClassTask($holder), 1);
                    }
                    return;
                }
            }
        }
    }
}