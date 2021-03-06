<?php

namespace hcf\combat\entity;

use hcf\HCF;
use hcf\HCFPlayer;
use hcf\translation\Translation;
use hcf\translation\TranslationException;
use PDO;
use pocketmine\entity\Entity;
use pocketmine\entity\Villager;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\utils\TextFormat;

class LogoutVillager extends Villager {

    /** @var string */
    private $name = "";

    /** @var int */
    private $time;

    public function initEntity(): void {
        parent::initEntity();
        $this->setMaxHealth(200);
        $this->setHealth(200);
    }

    /**
     * @param int $tickDiff
     *
     * @return bool
     * @noinspection NullPointerExceptionInspection
     */
    public function entityBaseTick(int $tickDiff = 1): bool {
        parent::entityBaseTick($tickDiff);
        $server = HCF::getInstance()->getServer();
        if($server->getPlayer($this->name) !== null) {
            $this->flagForDespawn();
            return false;
        }
        if((!$this->closed) && (!$this->isAlive())) {
            $this->flagForDespawn();
            return false;
        }
        if($this->name === null) {
            return false;
        }
        $time = 120 - (time() - $this->time);
        if($time <= 0) {
            $this->getLevel()->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_HURT);
            $this->flagForDespawn();
            return false;
        }
        $minutes = floor($time / 60);
        $seconds = $time % 60;
        if($seconds < 10) {
            $seconds = "0$seconds";
        }
        $this->setNameTag(TextFormat::YELLOW . TextFormat::BOLD . $this->name . " " . TextFormat::RESET . TextFormat::WHITE . floor($this->getHealth()) . TextFormat::RED . TextFormat::BOLD . " HP " . TextFormat::RESET . TextFormat::GREEN . "$minutes:$seconds");
        return $this->isAlive();
    }

    /**
     * @param EntityDamageEvent $source
     *
     * @throws TranslationException
     */
    public function attack(EntityDamageEvent $source): void {
        if(($this->getHealth() - $source->getFinalDamage()) > 0) {
            parent::attack($source);
            return;
        }
        $server = HCF::getInstance()->getServer();
        $stmt = HCF::getInstance()->getMySQLProvider()->getDatabase()->prepare("SELECT kills FROM players WHERE username = :username");
        $stmt->bindParam(":username", $this->name);
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            $kills = $row['kills'];
            $message = Translation::getMessage("death", [
                "name" => TextFormat::GREEN . $this->name . TextFormat::DARK_GRAY . "[" . TextFormat::DARK_GREEN . TextFormat::BOLD . $kills . TextFormat::RESET . TextFormat::DARK_GRAY . "]",
            ]);
            if($source instanceof EntityDamageByEntityEvent) {
                $killer = $source->getDamager();
                if($killer instanceof HCFPlayer) {
                    if($killer->getFaction() !== null && $killer->getFaction()->isInFaction($this->name) === true) {
                        $source->setCancelled();
                        $killer->sendMessage(Translation::getMessage("attackFactionAssociate"));
                        return;
                    }
                    $killer->addKills();
                    $message = Translation::getMessage("deathByPlayer", [
                        "name" => TextFormat::GREEN . $this->name . TextFormat::DARK_GRAY . "[" . TextFormat::DARK_GREEN . TextFormat::BOLD . $kills . TextFormat::RESET . TextFormat::DARK_GRAY . "]",
                        "killer" => TextFormat::RED . $killer->getName() . TextFormat::DARK_GRAY . "[" . TextFormat::DARK_RED . TextFormat::BOLD . $killer->getKills() . TextFormat::RESET . TextFormat::DARK_GRAY . "]"
                    ]);
                }
            }
            $server->broadcastMessage($message);
        }
        $stmt->closeCursor();
        
        $provider = HCF::getInstance()->getMySQLProvider();
        $username = $this->name;
        $time = 3600;
        $stmt = $provider->getDatabase()->prepare("SELECT lives FROM players WHERE username = :username");
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            $lives = $row['lives'];
            if($lives > 0) {
                $stmt = $provider->getDatabase()->prepare("UPDATE players SET lives = lives - 1, invincibilityTime = :invincibilityTime WHERE username = :username");
                $stmt->bindParam(":invincibilityTime",$time);
                $stmt->bindParam(":username",$username);
                $stmt->execute();
                $stmt->closeCursor();
            }
            else {
                $timestamp = time();
                $stmt = $provider->getDatabase()->prepare("UPDATE players SET deathBanTime = :deathBanTime, invincibilityTime = :invincibilityTime WHERE username = :username");
                $stmt->bindParam(":deathBanTime", $timestamp);
                $stmt->bindParam(":invincibilityTime", $time);
                $stmt->bindParam(":username",$username);
                $stmt->execute();
                $stmt->closeCursor();
            }
        }
        $stmt->closeCursor();

        $drops = [];
        $namedTag = $server->getOfflinePlayerData($this->name);
        $items = $namedTag->getListTag("Inventory")->getAllValues();
        foreach($items as $item) {
            $item = Item::nbtDeserialize($item);
            $drops[] = $item;
        }
        $level = $server->getDefaultLevel();
        $spawn = $level->getSpawnLocation();
        $namedTag->setTag(new ListTag("Inventory", [], NBT::TAG_Compound));
        $namedTag->setTag(new ListTag("Pos", [
            new DoubleTag("", $spawn->x),
            new DoubleTag("", $spawn->y),
            new DoubleTag("", $spawn->z)
        ], NBT::TAG_Double));
        $namedTag->setTag(new StringTag("Level", $level->getFolderName()));
        $server->saveOfflinePlayerData($this->name, $namedTag);
        foreach($drops as $item) {
            $this->getLevel()->dropItem($this, $item);
        }

        $this->getLevel()->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_HURT);
        $this->flagForDespawn();
    }

    /**
     * @param Entity $attacker
     * @param float $damage
     * @param float $x
     * @param float $z
     * @param float $base
     */
    public function knockBack(Entity $attacker, float $damage, float $x, float $z, float $base = 0.4): void {

    }

    /**
     * @param HCFPlayer $player
     */
    public function setPlayer(HCFPlayer $player): void {
        $this->name = $player->getName();
        $this->setNameTag(TextFormat::YELLOW . TextFormat::BOLD . $player->getName());
        $this->time = time();
    }
}