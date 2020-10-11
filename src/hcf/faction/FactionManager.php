<?php

namespace hcf\faction;

use hcf\faction\task\FactionHeartbeatTask;
use hcf\HCF;
use hcf\HCFPlayer;
use PDO;
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
        $stmt = $this->core->getMySQLProvider()->getDatabase()->query("SELECT name, x, y, z, minX, minZ, maxX, maxZ, level, members, allies, balance, dtr FROM factions");
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $home = null;
            if($row['x'] !== null && $row['y'] !== null && $row['z'] !== null && $row['level'] !== null) {
                $home = new Position($row['x'], $row['y'], $row['z'], HCF::getInstance()->getServer()->getLevelByName($row['level']));
            }
            $members = explode(",", $row['members']);
            $allies = explode(",", $row['allies']);
            $faction = new Faction($row['name'], $home, $members, $allies, $row['balance'], $row['dtr']);
            $claim = null;
            if($row['minX'] !== null && $row['minZ'] !== null && $row['maxX'] !== null && $row['maxZ'] !== null) {
                $firstPosition = new Position($row['minX'], 0, $row['minZ']);
                $secondPosition = new Position($row['maxX'], Level::Y_MAX, $row['maxZ']);
                $claim = new Claim($faction, $firstPosition, $secondPosition);
            }
            $faction->setClaim($claim);
            if($claim !== null) {
                $this->addClaim($claim);
            }
            $this->factions[$row['name']] = $faction;
        }
        $stmt->closeCursor();
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
        $stmt = HCF::getInstance()->getMySQLProvider()->getDatabase()->prepare("INSERT INTO factions(name, members) VALUES(:name, :members)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':members', $members);
        $stmt->execute();
        $stmt->closeCursor();
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
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("DELETE FROM factions WHERE name = :name");
        $stmt->bindParam(':name', $name);
        $stmt->execute();
        $stmt->closeCursor();
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