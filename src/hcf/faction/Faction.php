<?php

namespace hcf\faction;

use hcf\HCF;
use hcf\HCFPlayer;
use pocketmine\level\Position;
use pocketmine\utils\TextFormat;

class Faction {

    const RECRUIT = 0;

    const MEMBER = 1;

    const OFFICER = 2;

    const LEADER = 3;

    const MAX_MEMBERS = 4;

    const MAX_ALLIES = 0;

    const MAX_DTR = 4.1;

    const DTR_GENERATE_TIME = 900;

    const DTR_GENERATE_AMOUNT = 4.1;

    const DTR_FREEZE_TIME = 600;

    /** @var string */
    private $name;

    /** @var string[] */
    private $members = [];

    /** @var string[] */
    private $invites = [];

    /** @var string[] */
    private $allies = [];

    /** @var string[] */
    private $allyRequests = [];

    /** @var float */
    private $dtr = self::MAX_DTR;

    /** @var int */
    private $dtrFreezeTime = 0;

    /** @var null|int */
    private $dtrRegenerateTime = null;

    /** @var int */
    private $balance = 0;

    /** @var null|Position */
    private $home = null;

    /** @var null|Claim */
    private $claim = null;

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
        return in_array($player, $this->members);
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
        $stmt = HCF::getInstance()->getMySQLProvider()->getDatabase()->prepare("UPDATE factions SET members = ? WHERE name = ?");
        $stmt->bind_param("ss", $members, $this->name);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * @param string|HCFPlayer $player
     */
    public function removeMember($player): void {
        $name = $player instanceof HCFPlayer ? $player->getName() : $player;
        unset($this->members[array_search($name, $this->members)]);
        if($player instanceof HCFPlayer) {
            $player->setFaction(null);
            $player->setFactionRole(null);
        }
        $members = implode(",", $this->members);
        $stmt = HCF::getInstance()->getMySQLProvider()->getDatabase()->prepare("UPDATE factions SET members = ? WHERE name = ?");
        $stmt->bind_param("ss", $members, $this->name);
        $stmt->execute();
        $stmt->close();
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
        $stmt = HCF::getInstance()->getMySQLProvider()->getDatabase()->prepare("UPDATE factions SET allies = ? WHERE name = ?");
        $stmt->bind_param("ss", $allies, $this->name);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * @param Faction $faction
     */
    public function removeAlly(Faction $faction): void {
        unset($this->allies[array_search($faction->getName(), $this->allies)]);
        $allies = implode(",", $this->allies);
        $stmt = HCF::getInstance()->getMySQLProvider()->getDatabase()->prepare("UPDATE factions SET allies = ? WHERE name = ?");
        $stmt->bind_param("ss", $allies, $this->name);
        $stmt->execute();
        $stmt->close();
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
        return in_array($faction->getName(), $this->allies);
    }

    public function subtractDTR(): void {
        $this->dtr -= 1.0;
        if($this->dtr <= 0) {
            foreach($this->getOnlineMembers() as $member) {
                $member->sendTitle(TextFormat::BOLD . TextFormat::RED . "WARNING", TextFormat::GRAY . "Your faction is now raidable!");
            }
        }
        $this->dtrFreezeTime = time();
        $stmt = HCF::getInstance()->getMySQLProvider()->getDatabase()->prepare("UPDATE factions SET dtr = ? WHERE name = ?");
        $stmt->bind_param("ds", $this->dtr, $this->name);
        $stmt->execute();
        $stmt->close();
    }

    public function regenerateDTR(): void {
        $this->dtr += self::DTR_GENERATE_AMOUNT;
        $stmt = HCF::getInstance()->getMySQLProvider()->getDatabase()->prepare("UPDATE factions SET dtr = ? WHERE name = ?");
        $stmt->bind_param("ds", $this->dtr, $this->name);
        $stmt->execute();
        $stmt->close();
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
        $stmt = HCF::getInstance()->getMySQLProvider()->getDatabase()->prepare("UPDATE factions SET balance = balance + ? WHERE name = ?");
        $stmt->bind_param("is", $amount, $this->name);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * @param int $amount
     */
    public function subtractMoney(int $amount): void {
        $this->balance -= $amount;
        $stmt = HCF::getInstance()->getMySQLProvider()->getDatabase()->prepare("UPDATE factions SET balance = balance - ? WHERE name = ?");
        $stmt->bind_param("is", $amount, $this->name);
        $stmt->execute();
        $stmt->close();
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
        $stmt = HCF::getInstance()->getMySQLProvider()->getDatabase()->prepare("UPDATE factions SET x = ?, y = ?, z = ?, level = ? WHERE name = ?");
        $stmt->bind_param("iiiss", $x, $y, $z, $level, $this->name);
        $stmt->execute();
        $stmt->close();
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
        $stmt = HCF::getInstance()->getMySQLProvider()->getDatabase()->prepare("UPDATE factions SET minX = ?, minZ = ?, maxX = ?, maxZ = ? WHERE name = ?");
        $stmt->bind_param("iiiis", $minX, $minZ, $maxX, $maxZ, $this->name);
        $stmt->execute();
        $stmt->close();
    }

    public function removeClaim(): void {
        HCF::getInstance()->getFactionManager()->removeClaim($this->claim);
        $this->claim = null;
        $value = null;
        $stmt = HCF::getInstance()->getMySQLProvider()->getDatabase()->prepare("UPDATE factions SET minX = ?, minZ = ?, maxX = ?, maxZ = ? WHERE name = ?");
        $stmt->bind_param("iiiis", $value, $value, $value, $value, $this->name);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * @return Claim|null
     */
    public function getClaim(): ?Claim {
        return $this->claim;
    }
}