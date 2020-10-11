<?php

namespace hcf\faction;

use hcf\HCF;
use hcf\HCFPlayer;
use pocketmine\level\Position;
use pocketmine\utils\TextFormat;

class Faction {

    public const RECRUIT = 0;
    public const MEMBER = 1;
    public const OFFICER = 2;
    public const LEADER = 3;

    public const MAX_MEMBERS = 4;
    public const MAX_ALLIES = 0;
    public const MAX_DTR = 4.1;

    public const DTR_GENERATE_TIME = 900;
    public const DTR_GENERATE_AMOUNT = 4.1;
    public const DTR_FREEZE_TIME = 600;

    /** @var string */
    private $name;

    /** @var string[] */
    private $members;

    /** @var string[] */
    private $invites = [];

    /** @var string[] */
    private $allies;

    /** @var string[] */
    private $allyRequests = [];

    /** @var float */
    private $dtr;

    /** @var int */
    private $dtrFreezeTime = 0;

    /** @var null|int */
    private $dtrRegenerateTime;

    /** @var int */
    private $balance;

    /** @var null|Position */
    private $home;

    /** @var null|Claim */
    private $claim;

    /**
     * Faction constructor.
     *
     * @param string $name
     * @param Position|null $home
     * @param array $members
     * @param array $allies
     * @param int $balance
     * @param float $dtr
     */
    public function __construct(string $name, ?Position $home, array $members, array $allies, int $balance, float $dtr) {
        $this->name = $name;
        $this->home = $home;
        $this->members = $members;
        $this->allies = $allies;
        $this->balance = $balance;
        $this->dtr = $dtr;
    }

    public function tick(): void {
        if(count($this->getOnlineMembers()) === 0) {
            return;
        }
        if($this->isInDTRFreeze() === true) {
            return;
        }
        $maxDTR = count($this->members) < self::MAX_DTR ? count($this->members) + 0.1 : self::MAX_DTR;
        if($this->dtr > $maxDTR) {
            $this->dtr = $maxDTR;
        }
        if($this->dtr === $maxDTR) {
            return;
        }
        if($this->dtrRegenerateTime === null) {
            $this->dtrRegenerateTime = time();
            return;
        }
        if((time() - $this->dtrRegenerateTime) >= self::DTR_GENERATE_TIME) {
            $this->regenerateDTR();
            $this->dtrRegenerateTime = time();
            foreach($this->getOnlineMembers() as $member) {
                $member->sendMessage(TextFormat::GREEN . "+ " . self::DTR_GENERATE_AMOUNT . " DTR");
            }
        }
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getMembers(): array {
        return $this->members;
    }

    /**
     * @return HCFPlayer[]
     */
    public function getOnlineMembers(): array {
        $members = [];
        foreach($this->members as $member) {
            $player = HCF::getInstance()->getServer()->getPlayer($member);
            if($player !== null) {
                $members[] = $player;
            }
        }
        return $members;
    }

    /**
     * @param string|HCFPlayer $player
     *
     * @return bool
     */
    public function isInFaction($player): bool {
        $player = $player instanceof HCFPlayer ? $player->getName() : $player;
        return in_array($player, $this->members, true);
    }

    /**
     * @param HCFPlayer $player
     */
    public function demote(HCFPlayer $player): void {
        $player->setFactionRole($player->getFactionRole() - 1);
    }

    /**
     * @param HCFPlayer $player
     */
    public function promote(HCFPlayer $player): void {
        $player->setFactionRole($player->getFactionRole() + 1);
    }

    /**
     * @param HCFPlayer $player
     */
    public function addMember(HCFPlayer $player): void {
        $this->members[] = $player->getName();
        $player->setFaction($this);
        $player->setFactionRole(self::RECRUIT);
        $player->setFaction($this);
        $members = implode(",", $this->members);
        $stmt = HCF::getInstance()->getMySQLProvider()->getDatabase()->prepare("UPDATE factions SET members = :members WHERE name = :name");
        $stmt->bindParam(":members", $members);
        $stmt->bindParam(":name", $this->name);
        $stmt->execute();
        $stmt->closeCursor();
    }

    /**
     * @param string|HCFPlayer $player
     */
    public function removeMember($player): void {
        $name = $player instanceof HCFPlayer ? $player->getName() : $player;
        unset($this->members[array_search($name, $this->members, true)]);
        if($player instanceof HCFPlayer) {
            $player->setFaction(null);
            $player->setFactionRole(null);
        }
        $members = implode(",", $this->members);
        $stmt = HCF::getInstance()->getMySQLProvider()->getDatabase()->prepare("UPDATE factions SET members = :members WHERE name = :name");
        $stmt->bindParam(":members", $members);
        $stmt->bindParam(":name",$this->name);
        $stmt->execute();
        $stmt->closeCursor();
    }

    /**
     * @param HCFPlayer $player
     *
     * @return bool
     */
    public function isInvited(HCFPlayer $player): bool {
        return in_array($player->getName(), $this->invites);
    }

    /**
     * @param HCFPlayer $player
     */
    public function addInvite(HCFPlayer $player): void {
        $this->invites[] = $player->getName();
    }

    /**
     * @param HCFPlayer $player
     */
    public function removeInvite(HCFPlayer $player): void {
        unset($this->invites[array_search($player->getName(), $this->invites)]);
    }

    /**
     * @param Faction $faction
     *
     * @return bool
     */
    public function isAllying(Faction $faction): bool {
        return in_array($faction->getName(), $this->allyRequests);
    }

    /**
     * @param Faction $faction
     */
    public function addAllyRequest(Faction $faction): void {
        $this->allyRequests[] = $faction->getName();
    }

    /**
     * @param Faction $faction
     */
    public function addAlly(Faction $faction): void {
        $this->allies[] = $faction->getName();
        $allies = implode(",", $this->allies);
        $stmt = HCF::getInstance()->getMySQLProvider()->getDatabase()->prepare("UPDATE factions SET allies = :allies WHERE name = :name");
        $stmt->bindParam(":allies", $allies);
        $stmt->bindParam(":name",$this->name);
        $stmt->execute();
        $stmt->closeCursor();
    }

    /**
     * @param Faction $faction
     */
    public function removeAlly(Faction $faction): void {
        unset($this->allies[array_search($faction->getName(), $this->allies, true)]);
        $allies = implode(",", $this->allies);
        $stmt = HCF::getInstance()->getMySQLProvider()->getDatabase()->prepare("UPDATE factions SET allies = :allies WHERE name = :name");
        $stmt->bindParam(":allies", $allies);
        $stmt->bindParam(":name",$this->name);
        $stmt->execute();
        $stmt->closeCursor();
    }

    /**
     * @return array
     */
    public function getAllies(): array {
        return $this->allies;
    }

    /**
     * @param Faction $faction
     *
     * @return bool
     */
    public function isAlly(Faction $faction): bool {
        return in_array($faction->getName(), $this->allies, true);
    }

    public function subtractDTR(): void {
        $this->dtr -= 1.0;
        if($this->dtr <= 0) {
            foreach($this->getOnlineMembers() as $member) {
                $member->sendTitle(TextFormat::BOLD . TextFormat::RED . "WARNING", TextFormat::GRAY . "Your faction is now raidable!");
            }
        }
        $this->dtrFreezeTime = time();
        $stmt = HCF::getInstance()->getMySQLProvider()->getDatabase()->prepare("UPDATE factions SET dtr = :dtr WHERE name = :name");
        $stmt->bindParam(":dtr", $this->dtr);
        $stmt->bindParam(":name",$this->name);
        $stmt->execute();
        $stmt->closeCursor();
    }

    public function regenerateDTR(): void {
        $this->dtr += self::DTR_GENERATE_AMOUNT;
        $stmt = HCF::getInstance()->getMySQLProvider()->getDatabase()->prepare("UPDATE factions SET dtr = :dtr WHERE name = :name");
        $stmt->bindParam(":dtr", $this->dtr);
        $stmt->bindParam(":name",$this->name);
        $stmt->execute();
        $stmt->closeCursor();
    }

    /**
     * @return float
     */
    public function getDTR(): float {
        return $this->dtr;
    }

    /**
     * @return bool
     */
    public function isInDTRFreeze(): bool {
        return (time() - $this->dtrFreezeTime) < self::DTR_FREEZE_TIME;
    }

    /**
     * @return int
     */
    public function getDTRFreezeTime(): int {
        return self::DTR_FREEZE_TIME - (time() - $this->dtrFreezeTime);
    }

    /**
     * @param int $amount
     */
    public function addMoney(int $amount): void {
        $this->balance += $amount;
        $stmt = HCF::getInstance()->getMySQLProvider()->getDatabase()->prepare("UPDATE factions SET balance = balance + :amount WHERE name = :name");
        $stmt->bindParam(":amount", $amount);
        $stmt->bindParam(":name",$this->name);
        $stmt->execute();
        $stmt->closeCursor();
    }

    /**
     * @param int $amount
     */
    public function subtractMoney(int $amount): void {
        $this->balance -= $amount;
        $stmt = HCF::getInstance()->getMySQLProvider()->getDatabase()->prepare("UPDATE factions SET balance = balance - :amount WHERE name = :name");
        $stmt->bindParam(":amount", $amount);
        $stmt->bindParam(":name",$this->name);
        $stmt->execute();
        $stmt->closeCursor();
    }

    /**
     * @return int
     */
    public function getBalance(): int {
        return $this->balance;
    }

    /**
     * @param Position|null $position
     */
    public function setHome(?Position $position = null): void {
        $this->home = $position;
        $x = null;
        $y = null;
        $z = null;
        $level = null;
        if($position !== null) {
            $x = $position->getX();
            $y = $position->getY();
            $z = $position->getZ();
            $level = $position->getLevel()->getName();
        }
        $stmt = HCF::getInstance()->getMySQLProvider()->getDatabase()->prepare("UPDATE factions SET x = :x, y = :y, z = :z, level = :level WHERE name = :name");
        $stmt->bindParam(":x", $x);
        $stmt->bindParam(":y", $y);
        $stmt->bindParam(":z", $z);
        $stmt->bindParam(":level",$level);
        $stmt->bindParam(":name", $this->name);
        $stmt->execute();
        $stmt->closeCursor();
    }

    /**
     * @return Position|null
     */
    public function getHome(): ?Position {
        return $this->home;
    }

    /**
     * @param Claim|null $claim
     */
    public function setClaim(?Claim $claim): void {
        $this->claim = $claim;
    }

    /**
     * @param Claim $claim
     */
    public function setNewClaim(Claim $claim): void {
        $this->claim = $claim;
        HCF::getInstance()->getFactionManager()->addClaim($claim);
        $firstPosition = $claim->getFirstPosition();
        $secondPosition = $claim->getSecondPosition();
        $minX = min($firstPosition->getX(), $secondPosition->getX());
        $maxX = max($firstPosition->getX(), $secondPosition->getX());
        $minZ = min($firstPosition->getZ(), $secondPosition->getZ());
        $maxZ = max($firstPosition->getZ(), $secondPosition->getZ());
        $stmt = HCF::getInstance()->getMySQLProvider()->getDatabase()->prepare("UPDATE factions SET minX = :minX, minZ = :minZ, maxX = :maxX, maxZ = :maxZ WHERE name = :name");
        $stmt->bindParam(":minX", $minX);
        $stmt->bindParam(":minZ", $minZ);
        $stmt->bindParam(":maxX", $maxX);
        $stmt->bindParam(":maxZ", $maxZ);
        $stmt->bindParam(":name", $this->name);
        $stmt->execute();
        $stmt->closeCursor();
    }

    public function removeClaim(): void {
        HCF::getInstance()->getFactionManager()->removeClaim($this->claim);
        $this->claim = null;
        $value = null;
        $stmt = HCF::getInstance()->getMySQLProvider()->getDatabase()->prepare("UPDATE factions SET minX = :value, minZ = :value, maxX = :value, maxZ = :value WHERE name = :name");
        $stmt->bindParam(":value", $value);
        $stmt->bindParam(":name", $this->name);
        $stmt->execute();
        $stmt->closeCursor();
    }

    /**
     * @return Claim|null
     */
    public function getClaim(): ?Claim {
        return $this->claim;
    }
}