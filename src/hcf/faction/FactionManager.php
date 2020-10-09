<?php

namespace hcf\faction;

use hcf\faction\task\FactionHeartbeatTask;
use hcf\HCF;
use hcf\HCFPlayer;
use pocketmine\level\Level;
use pocketmine\level\Position;

class FactionManager {

    /** @var HCF */
    private $core;

    /** @var Faction[] */
    private $factions = [];

    /** @var Claim[] */
    private $claims = [];

    /**
     * FactionManager constructor.
     *
     * @param HCF $core
     */
    public function __construct(HCF $core) {
        $this->core = $core;
        $this->init();
        $core->getServer()->getPluginManager()->registerEvents(new FactionListener($core), $core);
        $core->getScheduler()->scheduleRepeatingTask(new FactionHeartbeatTask($this), 20);
    }

    public function init(): void {
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("SELECT name, x, y, z, minX, minZ, maxX, maxZ, level, members, allies, balance, dtr FROM factions");
        $stmt->execute();
        $stmt->bind_result($name, $x, $y, $z, $minX, $minZ, $maxX, $maxZ, $level, $members, $allies, $balance, $dtr);
        while($stmt->fetch()) {
            $home = null;
            if($x !== null && $y !== null && $z !== null && $level !== null) {
                $home = new Position($x, $y, $z, HCF::getInstance()->getServer()->getLevelByName($level));
            }
            $members = explode(",", $members);
            $allies = explode(",", $allies);
            $faction = new Faction($name, $home, $members, $allies, $balance, $dtr);
            $claim = null;
            if($minX !== null && $minZ !== null && $maxX !== null && $maxZ !== null) {
                $firstPosition = new Position($minX, 0, $minZ);
                $secondPosition = new Position($maxX, Level::Y_MAX, $maxZ);
                $claim = new Claim($faction, $firstPosition, $secondPosition);
            }
            $faction->setClaim($claim);
            if($claim !== null) {
                $this->addClaim($claim);
            }
            $this->factions[$name] = $faction;
        }
        $stmt->close();
    }

    /**
     * @return Faction[]
     */
    public function getFactions(): array {
        return $this->factions;
    }

    /**
     * @param string $name
     *
     * @return Faction|null
     */
    public function getFaction(string $name): ?Faction {
        return $this->factions[$name] ?? null;
    }

    /**
     * @param string $name
     * @param HCFPlayer $leader
     *
     * @throws FactionException
     */
    public function createFaction(string $name, HCFPlayer $leader): void {
        if(isset($this->factions[$name])) {
            throw new FactionException("Unable to override an existing faction!");
        }
        $members = $leader->getName();
        $stmt = HCF::getInstance()->getMySQLProvider()->getDatabase()->prepare("INSERT INTO factions(name, members) VALUES(?, ?)");
        $stmt->bind_param("ss", $name, $members);
        $stmt->execute();
        $stmt->close();
        $faction = new Faction($name, null, [$members], [], 0, Faction::MAX_DTR);
        $this->factions[$name] = $faction;
        $leader->setFaction($this->factions[$name]);
        $leader->setFactionRole(Faction::LEADER);
    }

    /**
     * @param string $name
     *
     * @throws FactionException
     */
    public function removeFaction(string $name): void {
        if(!isset($this->factions[$name])) {
            throw new FactionException("Non-existing faction is trying to be removed!");
        }
        $faction = $this->factions[$name];
        unset($this->factions[$name]);
        foreach($faction->getOnlineMembers() as $member) {
            $member->setFaction(null);
            $member->setFactionRole(null);
        }
        foreach($faction->getAllies() as $ally) {
            if(!isset($this->factions[$ally])) {
                continue;
            }
            $this->factions[$ally]->removeAlly($faction);
        }
        if($faction->getClaim() !== null) {
            $this->removeClaim($faction->getClaim());
        }
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("DELETE FROM factions WHERE name = ?");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * @param Claim $claim
     */
    public function addClaim(Claim $claim): void
    {
        foreach($claim->getChunkHashes() as $hash) {
            if(isset($this->claims[$hash])) {
                $this->core->getLogger()->notice($this->claims[$hash]->getFaction()->getName() . "'s chunk was overwritten by {$claim->getFaction()->getName()}.");
            }
            $this->claims[$hash] = $claim;
        }
    }

    /**
     * @param Claim $claim
     */
    public function removeClaim(Claim $claim): void
    {
        foreach($claim->getChunkHashes() as $hash) {
            unset($this->claims[$hash]);
        }
    }

    /**
     * @param Position $position
     *
     * @return Claim|null
     */
    public function getClaimInPosition(Position $position): ?Claim {
        $x = $position->getX();
        $z = $position->getZ();
        $hash = Level::chunkHash($x >> 4, $z >> 4);
        if(!isset($this->claims[$hash])) {
            return null;
        }
        if($this->claims[$hash]->isInClaim($position)) {
            return $this->claims[$hash];
        }
        return null;
    }

    /**
     * @param string $hash
     *
     * @return Claim|null
     */
    public function getClaimByHash(string $hash): ?Claim {
        return $this->claims[$hash] ?? null;
    }
}