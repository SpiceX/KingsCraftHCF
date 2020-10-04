<?php

namespace hcf\faction;

use hcf\HCF;
use hcf\HCFPlayer;
use hcf\translation\Translation;
use hcf\translation\TranslationException;
use pocketmine\block\Block;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\tile\Chest;
use pocketmine\tile\Sign;
use pocketmine\utils\TextFormat;

class FactionListener implements Listener {

    /** @var HCF */
    private $core;

    /**
     * FactionListener constructor.
     *
     * @param HCF $core
     */
    public function __construct(HCF $core) {
        $this->core = $core;
    }

    /**
     * @priority HIGHEST
     * @param PlayerJoinEvent $event
     */
    public function onPlayerJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof HCFPlayer) {
            return;
        }
        $faction = $player->getFaction();
        if($faction === null) {
            return;
        }
        foreach($faction->getOnlineMembers() as $member) {
            $member->sendMessage(Translation::GREEN . "{$player->getName()} is now online!");
        }
    }

    /**
     * @priority HIGHEST
     * @param PlayerQuitEvent $event
     */
    public function onPlayerQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof HCFPlayer) {
            return;
        }
        $faction = $player->getFaction();
        if($faction === null) {
            return;
        }
        foreach($faction->getOnlineMembers() as $member) {
            $member->sendMessage(Translation::RED . "{$player->getName()} is now offline!");
        }
    }

    /**
     * @priority LOWEST
     * @param PlayerInteractEvent $event
     *
     * @throws TranslationException
     */
    public function onPlayerInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if(!$player instanceof HCFPlayer) {
            return;
        }
        if($this->core->isEndOfTheWorld() === true or $player->isOp()) {
            return;
        }
        $claim = $this->core->getFactionManager()->getClaimInPosition($block->asPosition());
        if($claim === null) {
            return;
        }
        if($claim->getFaction()->getDTR() > 0) {
            $faction = $player->getFaction();
            if($faction === null or $claim->getFaction()->getName() !== $faction->getName() or $player->getFactionRole() === Faction::RECRUIT) {
                $player->sendMessage(Translation::getMessage("editClaimNotAllowed"));
                $event->setCancelled();
                return;
            }
            if($player->getFactionRole() === Faction::LEADER) {
                return;
            }
            $chest = $block->getLevel()->getTile($block);
            if($chest instanceof Chest) {
                for($face = 2; $face <= 5; $face++) {
                    $tile = $chest->getLevel()->getTile($chest->getSide($face));
                    if($tile instanceof Sign) {
                        if($tile->getLine(0) === TextFormat::LIGHT_PURPLE . TextFormat::BOLD . "Subclaim") {
                            $players = [$tile->getLine(1), $tile->getLine(2), $tile->getLine(3)];
                            foreach($players as $member) {
                                if($member === $player->getName()) {
                                    return;
                                }
                            }
                            $event->setCancelled();
                            $player->sendMessage(Translation::getMessage("noPermission"));
                            return;
                        }
                    }
                }
                if($chest->isPaired() === true) {
                    $pair = $chest->getPair();
                    for($face = 2; $face <= 5; $face++) {
                        $tile = $pair->getLevel()->getTile($pair->getSide($face));
                        if($tile instanceof Sign) {
                            if($tile->getLine(0) === TextFormat::LIGHT_PURPLE . TextFormat::BOLD . "Subclaim") {
                                $players = [$tile->getLine(1), $tile->getLine(2), $tile->getLine(3)];
                                foreach($players as $member) {
                                    if($member === $player->getName()) {
                                        return;
                                    }
                                }
                                $event->setCancelled();
                                $player->sendMessage(Translation::getMessage("noPermission"));
                                return;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @priority NORMAL
     * @param PlayerDeathEvent $event
     *
     * @throws TranslationException
     */
    public function onPlayerDeath(PlayerDeathEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof HCFPlayer) {
            return;
        }
        $faction = $player->getFaction();
        if($faction === null) {
            return;
        }
        $faction->subtractDTR();
        foreach($faction->getOnlineMembers() as $member) {
            $member->sendMessage(Translation::getMessage("dtrFreeze"));
        }
    }

    /**
     * @priority LOWEST
     * @param BlockPlaceEvent $event
     *
     * @throws TranslationException
     */
    public function onBlockPlace(BlockPlaceEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if(!$player instanceof HCFPlayer) {
            return;
        }
        if($this->core->isEndOfTheWorld() === true or $player->isOp()) {
            return;
        }
        $claim = $this->core->getFactionManager()->getClaimInPosition($block->asPosition());
        if($claim === null) {
            return;
        }
        if($claim->getFaction()->getDTR() > 0) {
            $faction = $player->getFaction();
            if($faction === null or $claim->getFaction()->getName() !== $faction->getName() or $player->getFactionRole() === Faction::RECRUIT) {
                $player->sendMessage(Translation::getMessage("editClaimNotAllowed"));
                $event->setCancelled();
                return;
            }
            if($player->getFactionRole() === Faction::LEADER) {
                return;
            }
            if($block->getId() === Block::HOPPER_BLOCK) {
                for($face = 0; $face <= 5; $face++) {
                    $chest = $block->getLevel()->getTile($block->getSide($face));
                    if($chest instanceof Chest) {
                        for($face = 2; $face <= 5; $face++) {
                            $tile = $chest->getLevel()->getTile($chest->getSide($face));
                            if($tile instanceof Sign) {
                                if($tile->getLine(0) === TextFormat::LIGHT_PURPLE . TextFormat::BOLD . "Subclaim") {
                                    $players = [$tile->getLine(1), $tile->getLine(2), $tile->getLine(3)];
                                    foreach($players as $member) {
                                        if($member === $player->getName()) {
                                            return;
                                        }
                                    }
                                    if($block->getId() === Block::HOPPER_BLOCK) {
                                        $event->setCancelled();
                                        $player->sendMessage(Translation::getMessage("noPermission"));
                                    }
                                    return;
                                }
                            }
                        }
                        if($chest->isPaired() === true) {
                            $pair = $chest->getPair();
                            for($face = 2; $face <= 5; $face++) {
                                $tile = $pair->getLevel()->getTile($pair->getSide($face));
                                if($tile instanceof Sign) {
                                    if($tile->getLine(0) === TextFormat::LIGHT_PURPLE . TextFormat::BOLD . "Subclaim") {
                                        $players = [$tile->getLine(1), $tile->getLine(2), $tile->getLine(3)];
                                        foreach($players as $member) {
                                            if($member === $player->getName()) {
                                                return;
                                            }
                                        }
                                        if($block->getId() === Block::HOPPER_BLOCK) {
                                            $event->setCancelled();
                                            $player->sendMessage(Translation::getMessage("noPermission"));
                                        }
                                        return;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @priority LOWEST
     * @param BlockBreakEvent $event
     *
     * @throws TranslationException
     */
    public function onBlockBreak(BlockBreakEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if(!$player instanceof HCFPlayer) {
            return;
        }
        if($this->core->isEndOfTheWorld() === true or $player->isOp()) {
            return;
        }
        $claim = $this->core->getFactionManager()->getClaimInPosition($block->asPosition());
        if($claim === null) {
            return;
        }
        if($claim->getFaction()->getDTR() > 0) {
            $faction = $player->getFaction();
            if($faction === null or $claim->getFaction()->getName() !== $faction->getName() or $player->getFactionRole() === Faction::RECRUIT) {
                $player->sendMessage(Translation::getMessage("editClaimNotAllowed"));
                $event->setCancelled();
                return;
            }
        }
        if($player->getFactionRole() === Faction::LEADER) {
            return;
        }
        $chest = $block->getLevel()->getTile($block);
        if($chest instanceof Chest) {
            for($face = 2; $face <= 5; $face++) {
                $tile = $chest->getLevel()->getTile($chest->getSide($face));
                if($tile instanceof Sign) {
                    if($tile->getLine(0) === TextFormat::LIGHT_PURPLE . TextFormat::BOLD . "Subclaim") {
                        $players = [$tile->getLine(1), $tile->getLine(2), $tile->getLine(3)];
                        foreach($players as $member) {
                            if($member === $player->getName()) {
                                return;
                            }
                        }
                        $event->setCancelled();
                        $player->sendMessage(Translation::getMessage("noPermission"));
                        return;
                    }
                }
            }
            if($chest->isPaired() === true) {
                $pair = $chest->getPair();
                for($face = 2; $face <= 5; $face++) {
                    $tile = $pair->getLevel()->getTile($pair->getSide($face));
                    if($tile instanceof Sign) {
                        if($tile->getLine(0) === TextFormat::LIGHT_PURPLE . TextFormat::BOLD . "Subclaim") {
                            $players = [$tile->getLine(1), $tile->getLine(2), $tile->getLine(3)];
                            foreach($players as $member) {
                                if($member === $player->getName()) {
                                    return;
                                }
                            }
                            $event->setCancelled();
                            $player->sendMessage(Translation::getMessage("noPermission"));
                            return;
                        }
                    }
                }
            }
        }
        if($chest instanceof Sign) {
            if($chest->getLine(0) === TextFormat::LIGHT_PURPLE . TextFormat::BOLD . "Subclaim") {
                $event->setCancelled();
                $player->sendMessage(Translation::getMessage("noPermission"));
                return;
            }
        }
    }

    /**
     * @priority LOWEST
     * @param EntityDamageEvent $event
     *
     * @throws TranslationException
     */
    public function onEntityDamage(EntityDamageEvent $event): void {
        $entity = $event->getEntity();
        if($entity instanceof HCFPlayer) {
            $faction = $entity->getFaction();
            if($faction === null) {
                return;
            }
            if($event instanceof EntityDamageByEntityEvent) {
                $damager = $event->getDamager();
                if(!$damager instanceof HCFPlayer) {
                    return;
                }
                $damagerFaction = $damager->getFaction();
                if($damagerFaction === null) {
                    return;
                }
                if($faction->isInFaction($damager) or $faction->isAlly($damagerFaction)) {
                    $damager->sendMessage(Translation::getMessage("attackFactionAssociate"));
                    $event->setCancelled();
                    return;
                }
            }
        }
    }

    /**
     * @priority NORMAL
     * @param SignChangeEvent $event
     *
     * @throws TranslationException
     */
    public function onSignChange(SignChangeEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof HCFPlayer) {
            return;
        }
        if($player->getFaction() === null) {
            $player->sendMessage(Translation::getMessage("beInFaction"));
            return;
        }
        if($player->getFactionRole() !== Faction::LEADER) {
            return;
        }
        $claim = $this->core->getFactionManager()->getClaimInPosition($event->getBlock()->asPosition());
        if($claim === null) {
            return;
        }
        if($claim->getFaction()->getName() !== $player->getFaction()->getName()) {
            return;
        }
        $lines = $event->getLines();
        if($lines[0] === "[subclaim]") {
            $event->setLine(0, TextFormat::LIGHT_PURPLE . TextFormat::BOLD . "Subclaim");
        }
    }
}