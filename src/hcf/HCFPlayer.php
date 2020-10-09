<?php

namespace hcf;

use hcf\faction\Faction;
use hcf\groups\Group;
use hcf\item\entity\GrapplingHook;
use hcf\translation\Translation;
use hcf\translation\TranslationException;
use hcf\wayPoint\WayPoint;
use libs\utils\BossBar;
use libs\utils\FloatingTextParticle;
use libs\utils\Scoreboard;
use libs\utils\UtilsException;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\permission\Permission;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class HCFPlayer extends Player {

    public const PUBLIC = 0;
    public const FACTION = 1;
    public const ALLY = 2;
    public const STAFF = 3;
    public const ARCHER = "Archer";
    public const BARD = "Bard";
    public const MINER = "Miner";
    public const ROGUE = "Rogue";

    public const EMPTY_INVENTORY = 0;
    public const INVENTORY_FULL = 1;


    /** @var int */
    public $cps = 0;

    /** @var bool */
    public $antiTrapperCooldown = false;

    /** @var bool */
    public $hasAntiTrapperEffect = false;

    /** @var bool */
    public $hasLumberAxeCooldown = false;

    /** @var null|GrapplingHook */
    private $grapplingHook;

    /** @var bool */
    private $logout = false;

    /** @var int */
    private $buffDelayTime = 0;

    /** @var int */
    private $enderPearlTime = 0;

    /** @var bool */
    private $frozen = false;

    /** @var bool */
    private $reclaim = false;

    /** @var bool */
    private $claiming = false;

    /** @var null|Position */
    private $clamingFirstPosition;

    /** @var null|Position */
    private $claimingSecondPosition;

    /** @var bool */
    private $runningCrateAnimation = false;

    /** @var int */
    private $bardEnergy = 0;

    /** @var null|HCFPlayer */
    private $archerTag;

    /** @var bool */
    private $voteChecking = false;

    /** @var bool */
    private $voted = false;

    /** @var null|string */
    private $class;

    /** @var null|int */
    private $muteTime;

    /** @var null|string */
    private $muteEffector;

    /** @var null|string */
    private $muteReason;

    /** @var bool */
    private $teleporting = false;

    /** @var int */
    private $chatMode = self::PUBLIC;

    /** @var bool */
    private $isChangingDimensions = false;

    /** @var null|HCFPlayer */
    private $focus;

    /** @var int */
    private $combatTag = 0;

    /** @var string */
    private $region = "";

    /** @var Scoreboard */
    private $scoreboard;

    /** @var BossBar */
    private $bossBar;

    /** @var FloatingTextParticle[] */
    private $floatingTexts = [];

    /** @var HCF */
    private $core;

    /** @var int */
    private $balance = 0;

    /** @var Group */
    private $group;

    /** @var string[] */
    private $permissions = [];

    /** @var string[] */
    private $tags = [];

    /** @var null|string */
    private $currentTag;

    /** @var null|Faction */
    private $faction;

    /** @var null|int */
    private $factionRole;

    /** @var null|int */
    private $invincibilityTime;

    /** @var int */
    private $lives = 0;

    /** @var int */
    private $kills = 0;

    /** @var WayPoint[] */
    private $wayPoints = [];

    /** @var bool */
    private $showWayPoint = true;

    /** @var bool */
    public $hasStarCooldown = false;

    /** @var bool */
    public $hasAntiTrapperCooldown = false;

    /** @var bool */
    public $hasFireworksCooldown = false;

    /** @var bool */
    public $hasInvisibilitySakCooldown = false;

    /** @var bool */
    public $hasTeleportationBallCooldown = false;

    /**
     * @return GrapplingHook|null
     */
    public function getGrapplingHook(): ?GrapplingHook {
        return $this->grapplingHook;
    }

    /**
     * @param GrapplingHook|null $hook
     */
    public function setGrapplingHook(?GrapplingHook $hook): void {
        $this->grapplingHook = $hook;
    }

    /**
     * @param bool $value
     */
    public function setLogout(bool $value = true): void {
        $this->logout = $value;
    }

    /**
     * @return bool
     */
    public function canLogout(): bool {
        if($this->isInvincible()) {
            return true;
        }
        $areaManager = $this->core->getAreaManager();
        $areas = $areaManager->getAreasInPosition($this->asPosition());
        $pvp = true;
        if($areas !== null) {
            foreach($areas as $area) {
                if($pvp === true && ($area->getPvpFlag() === false)) {
                    $pvp = false;
                }
            }
        }
        if($pvp === false) {
            return true;
        }
        return $this->logout;
    }

    /**
     * @return int
     */
    public function getBuffDelayTime(): int {
        return $this->buffDelayTime;
    }

    public function setBuffDelayTime(): void {
        $this->buffDelayTime = time();
    }

    /**
     * @return int
     */
    public function getEnderPearlTime(): int {
        return $this->enderPearlTime;
    }

    /**
     * @param int|null $time
     */
    public function setEnderPearlTime(int $time): void {
        $this->enderPearlTime = $time ?? time();
    }

    /**
     * @return bool
     */
    public function isFrozen(): bool {
        return $this->frozen;
    }

    /**
     * @param bool $value
     */
    public function setFrozen(bool $value = true): void {
        $this->frozen = $value;
    }

    /**
     * @return bool
     */
    public function hasReclaimed(): bool {
        return $this->reclaim;
    }

    /**
     * @param bool $value
     */
    public function setReclaimed(bool $value = true): void {
        $this->reclaim = $value;
        $claimed = $value ? 1 : 0;
        $uuid = $this->getRawUniqueId();
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("UPDATE players SET reclaim = ? WHERE uuid = ?");
        $stmt->bind_param("is", $claimed, $uuid);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * @return bool
     */
    public function isClaiming(): bool {
        return $this->claiming;
    }

    /**
     * @param bool $value
     */
    public function setClaiming(bool $value = true): void {
        $this->claiming = $value;
    }

    /**
     * @return Position|null
     */
    public function getFirstClaimPosition(): ?Position {
        return $this->clamingFirstPosition;
    }

    /**
     * @return Position|null
     */
    public function getSecondClaimPosition(): ?Position {
        return $this->claimingSecondPosition;
    }

    /**
     * @param Position|null $position
     */
    public function setFirstClaimingPosition(?Position $position): void {
        $this->clamingFirstPosition = $position;
    }

    /**
     * @param Position|null $position
     */
    public function setSecondClaimingPosition(?Position $position): void {
        $this->claimingSecondPosition = $position;
    }

    /**
     * @return bool
     */
    public function isRunningCrateAnimation(): bool {
        return $this->runningCrateAnimation;
    }

    /**
     * @param bool $value
     */
    public function setRunningCrateAnimation(bool $value = true): void {
        $this->runningCrateAnimation = $value;
    }

    /**
     * @param HCFPlayer|null $player
     */
    public function archerTag(?HCFPlayer $player): void {
        if(($this->archerTag !== null) && $this->archerTag->isOnline()) {
            $this->archerTag->sendData($this, [
                Entity::DATA_NAMETAG => [
                    Entity::DATA_TYPE_STRING,
                    $this->archerTag->getGroup()->getTagFormatFor($this->archerTag, [
                        "faction_rank" => $this->archerTag->getFactionRoleToString(),
                        "faction" => ($faction = $this->archerTag->getFaction()) instanceof Faction ? $faction->getName() : ""
                    ])
                ]
            ]);
        }
        if($player !== null) {
            $player->sendData($this, [
                Entity::DATA_NAMETAG => [
                    Entity::DATA_TYPE_STRING,
                    TextFormat::RED . TextFormat::BOLD . $player->getName()
                ]
            ]);
        }
        $this->archerTag = $player;
    }

    /**
     * @return HCFPlayer|null
     */
    public function getArcherTagPlayer(): ?HCFPlayer {
        return $this->archerTag;
    }

    /**
     * @param int $amount
     */
    public function setBardEnergy(int $amount): void {
        $this->bardEnergy = max(0, $amount);
        $this->bossBar->update(TextFormat::LIGHT_PURPLE . TextFormat::BOLD . "Bard Energy: " . TextFormat::RESET . TextFormat::WHITE . $this->bardEnergy, $this->bardEnergy / 100);
    }

    /**
     * @return int
     */
    public function getBardEnergy(): int {
        return $this->bardEnergy;
    }

    /**
     * @param bool $value
     */
    public function setCheckingForVote(bool $value = true): void {
        $this->voteChecking = $value;
    }

    /**
     * @return bool
     */
    public function isCheckingForVote(): bool {
        return $this->voteChecking;
    }

    /**
     * @return bool
     */
    public function hasVoted(): bool {
        return $this->voted;
    }

    /**
     * @param bool $value
     */
    public function setVoted(bool $value = true): void {
        $this->voted = $value;
    }

    /**
     * @return string|null
     */
    public function getClass(): ?string {
        return $this->class;
    }

    /**
     * @param string|null $class
     */
    public function setClass(?string $class): void {
        $this->class = $class;
    }

    /**
     * @param int|null $time
     * @param string|null $effector
     * @param string|null $reason
     */
    public function setMuted(?int $time, ?string $effector, ?string $reason): void {
        $uuid = $this->getRawUniqueId();
        if($time === null && $effector === null && $reason === null) {
            $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("DELETE FROM mutes WHERE uuid = ?;");
            $stmt->bind_param("s", $uuid);
            $stmt->execute();
            $stmt->close();
        }
        else {
            $name = $this->getName();
            $stmt = $this->getCore()->getMySQLProvider()->getDatabase()->prepare("INSERT INTO mutes(uuid, username, effector, reason, expiration) VALUES(?, ?, ?, ?, ?);");
            $stmt->bind_param("ssssi", $uuid, $name, $effector, $reason, $time);
            $stmt->execute();
            $stmt->close();
        }
        $this->muteTime = $time;
        $this->muteEffector = $effector;
        $this->muteReason = $reason;
    }

    /**
     * @return int|null
     */
    public function getMuteTime(): ?int {
        return $this->muteTime;
    }

    /**
     * @return string|null
     */
    public function getMuteEffector(): ?string {
        return $this->muteEffector;
    }

    /**
     * @return string|null
     */
    public function getMuteReason(): ?string {
        return $this->muteReason;
    }

    /**
     * @return bool
     */
    public function isTeleporting(): bool {
        return $this->teleporting;
    }

    /**
     * @param bool $value
     */
    public function setTeleporting(bool $value = true): void {
        $this->teleporting = $value;
    }

    /**
     * @return int
     */
    public function getChatMode(): int {
        return $this->chatMode;
    }

    /**
     * @return string
     */
    public function getChatModeToString(): string {
        switch($this->chatMode) {
            case self::PUBLIC:
                return "public";
            case self::FACTION:
                return "faction";
            case self::ALLY:
                return "ally";
            case self::STAFF:
                return "staff";
            default:
                return "unknown";
        }
    }

    /**
     * @param int $mode
     */
    public function setChatMode(int $mode): void {
        $this->chatMode = $mode;
    }

    /**
     * @return bool
     */
    public function isChangingDimension(): bool {
        return $this->isChangingDimensions;
    }

    /**
     * @param bool $value
     */
    public function setChangingDimensions(bool $value): void {
        $this->isChangingDimensions = $value;
    }

    /**
     * @param HCFPlayer|null $player
     */
    public function focus(?HCFPlayer $player): void
    {
        if(($this->focus !== null) && $this->focus->isOnline()) {
            $this->focus->sendData($this, [
                Entity::DATA_NAMETAG => [
                    Entity::DATA_TYPE_STRING,
                    $this->focus->getGroup()->getTagFormatFor($this->focus, [
                        "faction_rank" => $this->focus->getFactionRoleToString(),
                        "faction" => ($faction = $this->focus->getFaction()) instanceof Faction ? $faction->getName() : ""
                    ])
                ]
            ]);
        }
        if($player !== null) {
            $player->sendData($this, [
                Entity::DATA_NAMETAG => [
                    Entity::DATA_TYPE_STRING,
                    TextFormat::YELLOW . TextFormat::BOLD . $player->getName()
                ]
            ]);
        }
        $this->focus = $player;
    }

    /**
     * @param bool $value
     */
    public function combatTag(bool $value = true): void {
        if($value) {
            $this->combatTag = time();
            return;
        }
        $this->combatTag = 0;
    }

    /**
     * @return bool
     */
    public function isTagged(): bool {
        return (time() - $this->combatTag) <= 30;
    }

    /**
     * @return int
     */
    public function getCombatTagTime(): int {
        return $this->combatTag;
    }

    /**
     * @return string
     */
    public function getRegion(): string {
        return $this->region;
    }

    /**
     * @return string
     */
    public function getRegionByPosition(): string {
        $areaManager = $this->core->getAreaManager();
        $areas = $areaManager->getAreasInPosition($this->asPosition());
        if($areas !== null) {
            $region = "Unknown";
            foreach($areas as $area) {
                $region = $area->getName() . " (PvP)";
                if($area->getPvpFlag() === false) {
                    $region = $area->getName();
                }
            }
            return $region;
        }
        // $this->core->getRoadManager()->isInRoad($this->asPosition()) &&
        if($this->getLevel()->getName() === $this->getServer()->getDefaultLevel()->getName()) {
            $region = "§cWilderness §e(§cDeathban§e)";
            /*if($this->getFloorZ() > 61 && $this->getFloorX() > -16 && $this->getFloorX() < 16 && Utils::getCompassDirection($this->getYaw() - 90) === 'S') {
                $region = "§cSouth Road §e(§cDeathban§e)";
            }
            elseif($this->getFloorZ() > 70 && $this->getFloorX() < -16 && $this->getFloorX() > 16 && Utils::getCompassDirection($this->getYaw() - 90) === 'N') {
                $region = "§cNorth Road §e(§cDeathban§e)";
            }
            elseif($this->getFloorX() > 66 && $this->getFloorZ() < -16 && $this->getFloorZ() > 16 && Utils::getCompassDirection($this->getYaw() - 90) === 'E') {
                $region = "§cEast Road §e(§cDeathban§e)";
            }
            elseif($this->getFloorX() > -66 && $this->getFloorX() > -16 && $this->getFloorX() < 16 && Utils::getCompassDirection($this->getYaw() - 90) === 'W') {
                $region = "§cWest Road §e(§cDeathban§e)";
            }*/
            return $region;
        }
        $claim = $this->core->getFactionManager()->getClaimInPosition($this->asPosition());
        if($claim !== null) {
            return $claim->getFaction()->getName();
        }
        return "§cWilderness §e(§cDeathban§e)";
    }

    /**
     * @return int
     */
    public function getInventoryState(): int
    {
        if (!$this->getInventory()->canAddItem(Item::get(Item::TALL_GRASS))){
            return self::INVENTORY_FULL;
        }
        $empty = true;
        foreach ($this->getInventory()->getContents(true) as $item) {
            if ($item->getId() !== Item::AIR){
                $empty = false;
            }
        }
        return $empty ? self::EMPTY_INVENTORY : self::INVENTORY_FULL;
    }

    /**
     * @return int
     */
    public function getArmorInventoryState(): int
    {
        if (!$this->getArmorInventory()->canAddItem(Item::get(Item::TALL_GRASS))){
            return self::INVENTORY_FULL;
        }
        $empty = true;
        foreach ($this->getArmorInventory()->getContents(true) as $item) {
            if ($item->getId() !== Item::AIR){
                $empty = false;
            }
        }
        return $empty ? self::EMPTY_INVENTORY : self::INVENTORY_FULL;
    }

    /**
     * @param string $region
     */
    public function setRegion(string $region): void {
        $this->region = $region;
    }

    /**
     * @return Scoreboard
     */
    public function getScoreboard(): Scoreboard {
        return $this->scoreboard;
    }

    /**
     * @return BossBar
     */
    public function getBossBar(): BossBar {
        return $this->bossBar;
    }

    /**
     * @return FloatingTextParticle[]
     */
    public function getFloatingTexts(): array {
        return $this->floatingTexts;
    }

    /**
     * @param string $identifier
     *
     * @return FloatingTextParticle|null
     */
    public function getFloatingText(string $identifier): ?FloatingTextParticle {
        return $this->floatingTexts[$identifier] ?? null;
    }

    /**
     * @param Position $position
     * @param string $identifier
     * @param string $message
     */
    public function addFloatingText(Position $position, string $identifier, string $message): void {
        $floatingText = new FloatingTextParticle($this, $position, $identifier, $message);
        $this->floatingTexts[$identifier] = $floatingText;
        $floatingText->sendChangesTo($this);
    }

    /**
     * @param string $identifier
     *
     * @throws UtilsException
     */
    public function removeFloatingText(string $identifier): void {
        $floatingText = $this->getFloatingText($identifier);
        if($floatingText === null) {
            throw new UtilsException("Failed to despawn floating text: $identifier");
        }
        $floatingText->despawn($this);
        unset($this->floatingTexts[$identifier]);
    }

    /**
     * @return HCF
     */
    public function getCore(): HCF {
        return $this->core;
    }

    /**
     * @param HCF $core
     *
     * @return bool
     * @throws TranslationException
     */
    public function load(HCF $core): bool {
        $this->scoreboard = new Scoreboard($this);
        $this->bossBar = new BossBar($this);
        $this->core = $core;
        if(!$this->isRegistered()) {
            $this->register();
        }
        $uuid = $this->getRawUniqueId();
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("SELECT effector, reason, expiration FROM bans WHERE uuid = ?;");
        $stmt->bind_param("s", $uuid);
        $stmt->execute();
        $stmt->bind_result($effector, $reason, $expiration);
        $stmt->fetch();
        $stmt->close();
        if($effector !== null && $reason !== null) {
            $time = "Permanent";
            if($expiration !== null) {
                $time = $expiration - time();
                $days = floor($time / 86400);
                $hours = floor(($time / 3600) % 24);
                $minutes = floor(($time / 60) % 60);
                $seconds = $time % 60;
                $time = "$days days, $hours hours, $minutes minutes, $seconds seconds";
            }
            $this->close(null, Translation::getMessage("banMessage", [
                "name" => $effector,
                "reason" => $reason,
                "time" => $time
            ]));
            return false;
        }
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("SELECT effector, reason, expiration FROM mutes WHERE uuid = ?;");
        $stmt->bind_param("s", $uuid);
        $stmt->execute();
        $stmt->bind_result($effector, $reason, $expiration);
        $stmt->fetch();
        $stmt->close();
        $this->setMuted($expiration, $effector, $reason);
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("SELECT name, x, y, z, level FROM wayPoints WHERE uuid = ?;");
        $stmt->bind_param("s", $uuid);
        $stmt->execute();
        $stmt->bind_result($name, $x, $y, $z, $levelName);
        while($stmt->fetch()) {
            $level = $core->getServer()->getLevelByName($levelName);
            $this->wayPoints[$name] = new WayPoint($name, $x, $y, $z, $level);
        }
        $stmt->close();
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("SELECT faction, factionRole, balance, groupId, permissions, tags, currentTag, invincibilityTime, lives, deathBanTime, reclaim, kills FROM players WHERE uuid = ?");
        $stmt->bind_param("s", $uuid);
        $stmt->execute();
        $stmt->bind_result($faction, $factionRole, $balance, $groupId, $permissions, $tags, $currentTag, $invincibilityTime, $lives, $deathBanTime, $reclaim, $kills);
        $stmt->fetch();
        $stmt->close();
        $this->group = $core->getGroupManager()->getGroupByIdentifier($groupId);
        if($deathBanTime !== null) {
            $timeLeft = $this->group->getDeathBanTime() - (time() - $deathBanTime);
            if($timeLeft > 0) {
                $days = floor($timeLeft / 86400);
                $hours = floor(($timeLeft / 3600) % 24);
                $minutes = floor(($timeLeft / 60) % 60);
                $seconds = $timeLeft % 60;
                $this->close(null, Translation::getMessage("banMessage", [
                    "name" => "Operator",
                    "reason" => "Death ban",
                    "time" => "$days days, $hours hours, $minutes minutes, $seconds seconds"
                ]));
                return false;
            }
        }
        if($faction !== null) {
            $faction = $core->getFactionManager()->getFaction($faction);
            if(($faction !== null) && $faction->isInFaction($this)) {
                $this->faction = $faction;
                $this->factionRole = $factionRole;
            }
        }
        $this->balance = $balance;
        $this->permissions = explode(",", $permissions);
        $this->tags = explode(",", $tags);
        $this->currentTag = $currentTag;
        $this->reclaim = (bool)$reclaim;
        $this->setInvincible($invincibilityTime);
        //$this->setDisplayName($this->getDisplayName() . " " . $currentTag);
        /*$this->setNameTag($this->getGroup()->getTagFormatFor($this, [
            "faction_rank" => $this->getFactionRoleToString(),
            "faction" => ($faction = $this->getFaction()) instanceof Faction ? $faction->getName() : ""
        ]));*/
        //$this->setScoreTag(TextFormat::WHITE . floor($this->getHealth()) . TextFormat::RED . TextFormat::BOLD . " HP");
        $this->lives = $lives;
        $this->kills = $kills;
        return true;
    }

    public function register(): void {
        $uuid = $this->getRawUniqueId();
        $username = $this->getName();
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("INSERT INTO players(uuid, username) VALUES(?, ?)");
        $stmt->bind_param("ss", $uuid, $username);
        $stmt->execute();
        $stmt->close();
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("INSERT INTO kitCooldowns(uuid, username) VALUES(?, ?)");
        $stmt->bind_param("ss", $uuid, $username);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * @return bool
     */
    public function isRegistered(): bool {
        $uuid = $this->getRawUniqueId();
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("SELECT username FROM players WHERE uuid = ?");
        if ($stmt === false){
            return true;
        }
        $stmt->bind_param("s", $uuid);
        $stmt->execute();
        $stmt->bind_result($result);
        $stmt->fetch();
        $stmt->close();
        $uuid = $this->getRawUniqueId();
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("SELECT username FROM kitCooldowns WHERE uuid = ?");
        if ($stmt === false){
            return true;
        }
        $stmt->bind_param("s", $uuid);
        $stmt->execute();
        $stmt->bind_result($result2);
        $stmt->fetch();
        $stmt->close();
        return $result !== null && $result2 !== null;
    }

    /**
     * @return int
     */
    public function getBalance(): int {
        return $this->balance;
    }

    /**
     * @param int $amount
     */
    public function addToBalance(int $amount): void {
        $this->balance += $amount;
        $uuid = $this->getRawUniqueId();
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("UPDATE players SET balance = balance + ? WHERE uuid = ?");
        $stmt->bind_param("is", $amount, $uuid);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * @param int $amount
     */
    public function subtractFromBalance(int $amount): void {
        $this->balance -= $amount;
        $uuid = $this->getRawUniqueId();
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("UPDATE players SET balance = balance - ? WHERE uuid = ?");
        $stmt->bind_param("is", $amount, $uuid);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * @param int $amount
     */
    public function setBalance(int $amount): void {
        $this->balance = $amount;
        $uuid = $this->getRawUniqueId();
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("UPDATE players SET balance = ? WHERE uuid = ?");
        $stmt->bind_param("is", $amount, $uuid);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * @return Group
     */
    public function getGroup(): Group {
        return $this->group;
    }

    /**
     * @param Group $group
     */
    public function setGroup(Group $group): void {
        $this->group = $group;
        $groupId = $group->getIdentifier();
        $uuid = $this->getRawUniqueId();
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("UPDATE players SET groupId = ? WHERE uuid = ?");
        $stmt->bind_param("is", $groupId, $uuid);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * @param Permission|string $name
     *
     * @return bool
     */
    public function hasPermission($name): bool {
        if(in_array($name, $this->permissions, true)) {
            return true;
        }
        if(in_array($name, $this->group->getPermissions(), true)) {
            return true;
        }
        return parent::hasPermission($name);
    }

    /**
     * @param string $permission
     */
    public function addPermission(string $permission): void {
        $this->permissions[] = $permission;
        $this->permissions = array_unique($this->permissions);
        $uuid = $this->getRawUniqueId();
        $permissions = implode(",", $this->permissions);
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("UPDATE players SET permissions = ? WHERE uuid = ?");
        $stmt->bind_param("ss", $permissions, $uuid);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * @return array
     */
    public function getTags(): array {
        return $this->tags;
    }

    /**
     * @param string $tag
     */
    public function setCurrentTag(string $tag): void {
        $this->currentTag = $tag;
        $uuid = $this->getRawUniqueId();
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("UPDATE players SET currentTag = ? WHERE uuid = ?");
        $stmt->bind_param("ss", $tag, $uuid);
        $stmt->execute();
        $stmt->close();
        $this->setDisplayName($this->getName() . " " . $tag);
    }

    /**
     * @param string $tag
     */
    public function addTag(string $tag): void {
        $this->tags[] = $tag;
        $uuid = $this->getRawUniqueId();
        $tags = implode(",", $this->tags);
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("UPDATE players SET tags = ? WHERE uuid = ?");
        $stmt->bind_param("ss", $tags, $uuid);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * @return Faction|null
     */
    public function getFaction(): ?Faction {
        return $this->faction;
    }

    /**
     * @param Faction|null $faction
     */
    public function setFaction(?Faction $faction): void {
        $this->faction = $faction;
        /*$this->setNameTag($this->getGroup()->getTagFormatFor($this, [
            "faction_rank" => $this->getFactionRoleToString(),
            "faction" => ($faction = $this->getFaction()) instanceof Faction ? $faction->getName() : ""
        ]));*/
        $factionName = $faction instanceof Faction ? $faction->getName() : null;
        $uuid = $this->getRawUniqueId();
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("UPDATE players SET faction = ? WHERE uuid = ?");
        $stmt->bind_param("ss", $factionName, $uuid);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * @return int|null
     */
    public function getFactionRole(): ?int {
        return $this->factionRole;
    }

    /**
     * @return string
     */
    public function getFactionRoleToString(): string {
        switch($this->factionRole) {
            case Faction::RECRUIT:
                return "-";
            case Faction::OFFICER:
                return "*";
            case Faction::LEADER:
                return "**";
            case Faction::MEMBER:
            default:
                return "";
        }
    }

    /**
     * @param int|null $role
     */
    public function setFactionRole(?int $role): void {
        $this->factionRole = $role;
        /*$this->setNameTag($this->getGroup()->getTagFormatFor($this, [
            "faction_rank" => $this->getFactionRoleToString(),
            "faction" => ($faction = $this->getFaction()) instanceof Faction ? $faction->getName() : ""
        ]));*/
        $uuid = $this->getRawUniqueId();
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("UPDATE players SET factionRole = ? WHERE uuid = ?");
        $stmt->bind_param("is", $role, $uuid);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * @param int|null $time
     */
    public function setInvincible(?int $time): void {
        $this->invincibilityTime = $time ?? 3600;
        $uuid = $this->getRawUniqueId();
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("UPDATE players SET invincibilityTime = ? WHERE uuid = ?");
        $stmt->bind_param("is", $this->invincibilityTime, $uuid);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * @return bool
     */
    public function isInvincible(): bool {
        return $this->invincibilityTime > 0;
    }

    /**
     * @return bool
     */
    public function canDeductInvincibilityTime(): bool {
        $areas = $this->core->getAreaManager()->getAreasInPosition($this);
        if($areas === null) {
            return true;
        }
        foreach($areas as $area) {
            if($area->getPvpFlag() === false) {
                return false;
            }
        }
        return $this->invincibilityTime > 0;
    }

    public function subtractInvincibilityTime(): void {
        --$this->invincibilityTime;
    }

    /**
     * @return int
     */
    public function getInvincibilityTime(): int {
        return $this->invincibilityTime;
    }

    /**
     * @param int $lives
     */
    public function addLives(int $lives): void {
        $this->lives += $lives;
        $uuid = $this->getRawUniqueId();
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("UPDATE players SET lives = ? WHERE uuid = ?");
        $stmt->bind_param("is", $this->lives, $uuid);
        $stmt->execute();
        $stmt->close();
    }

    public function removeLife(): void {
        if($this->lives > 0) {
            --$this->lives;
        }
        $uuid = $this->getRawUniqueId();
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("UPDATE players SET lives = ? WHERE uuid = ?");
        $stmt->bind_param("is", $this->lives, $uuid);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * @return int
     */
    public function getLives(): int {
        return $this->lives;
    }

    public function playXpLevelUpSound(): void {
        $this->addXp(1000);
        $this->subtractXp(1000);
    }

    /**
     * @param int $amount
     */
    public function addKills(int $amount = 1): void {
        $this->kills += $amount;
        $uuid = $this->getRawUniqueId();
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("UPDATE players SET kills = ? WHERE uuid = ?");
        $stmt->bind_param("is", $this->kills, $uuid);
        $stmt->execute();
        $stmt->close();
    }
    /**
     * @return int
     */
    public function getKills(): int {
        return $this->kills;
    }

    /**
     * @param string $name
     *
     * @return WayPoint|null
     */
    public function getWayPoint(string $name): ?WayPoint {
        return $this->wayPoints[$name] ?? null;
    }

    /**
     * @param WayPoint $wayPoint
     */
    public function addWayPoint(WayPoint $wayPoint): void {
        $name = $wayPoint->getName();
        $this->wayPoints[$name] = $wayPoint;
        $username = $this->getName();
        $uuid = $this->getRawUniqueId();
        $x = $this->getFloorX();
        $y = $this->getFloorY();
        $z = $this->getFloorZ();
        $level = $this->getLevel()->getName();
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("INSERT INTO wayPoints(uuid, username, name, x, y, z, level) VALUES(?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiiis", $uuid, $username, $name, $x, $y, $z, $level);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * @param string $name
     */
    public function removeWayPoint(string $name): void {
        unset($this->wayPoints[$name]);
        $uuid = $this->getRawUniqueId();
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("DELETE FROM wayPoints WHERE uuid = ? AND name = ?");
        $stmt->bind_param("ss", $uuid, $name);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * @return WayPoint[]
     */
    public function getWayPoints(): array {
        return $this->wayPoints;
    }

    /**
     * @return bool
     */
    public function isShowingWayPoint(): bool {
        return $this->showWayPoint;
    }

    /**
     * @param bool $value
     */
    public function setShowWayPoint(bool $value = true): void {
        $this->showWayPoint = $value;
    }
}