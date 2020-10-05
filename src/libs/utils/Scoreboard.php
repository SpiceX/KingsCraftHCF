<?php

namespace libs\utils;

use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\Player;

class Scoreboard {

    public const CRITERIA_NAME = "dummy";
    public const MIN_LINES = 1;
    public const MAX_LINES = 15;
    public const SORT_ASCENDING = 0;
    public const SORT_DESCENDING = 1;
    public const SLOT_LIST = "list";
    public const SLOT_SIDEBAR = "sidebar";
    public const SLOT_BELOW_NAME = "belowname";

    /** @var Player */
    private $owner;

    /** @var bool */
    private $isSpawned = false;

    /** @var string[] */
    private $lines = [];

    /**
     * ScoreFactory constructor.
     *
     * @param Player $owner
     */
    public function __construct(Player $owner) {
        $this->owner = $owner;
    }

    /**
     * @return Player
     */
    public function getOwner(): Player {
        return $this->owner;
    }

    /**
     * @param string $title
     * @param int $slotOrder
     * @param string $displaySlot
     */
    public function spawn(string $title, int $slotOrder = self::SORT_ASCENDING, string $displaySlot = self::SLOT_SIDEBAR): void
    {
        if($this->isSpawned) {
            return;
        }
        $pk = new SetDisplayObjectivePacket();
        $pk->displaySlot = $displaySlot;
        $pk->objectiveName = $this->owner->getName();
        $pk->displayName = $title;
        $pk->criteriaName = self::CRITERIA_NAME;
        $pk->sortOrder = $slotOrder;
        $this->owner->sendDataPacket($pk);
        $this->isSpawned = true;
    }

    public function despawn(): void
    {
        if(!$this->isSpawned) {
            return;
        }
        $pk = new RemoveObjectivePacket();
        $pk->objectiveName = $this->owner->getName();
        $this->owner->sendDataPacket($pk);
        $this->isSpawned = false;
    }

    /**
     * @return bool
     */
    public function isSpawned(): bool {
        return $this->isSpawned;
    }

    /**
     * @param int $line
     * @param string $message
     *
     * @throws UtilsException
     */
    public function setScoreLine(int $line, string $message): void {
        if($this->isSpawned === false) {
            throw new UtilsException("{$this->owner->getName()}'s scoreboard has not spawned yet!'");
        }
        if($line < self::MIN_LINES || $line > self::MAX_LINES) {
            throw new UtilsException("Line number is out of range!");
        }
        $entry = new ScorePacketEntry();
        $entry->objectiveName = $this->owner->getName();
        $entry->type = $entry::TYPE_FAKE_PLAYER;
        $entry->customName = $message . str_repeat(" ", $line);
        $entry->score = $line;
        $entry->scoreboardId = $line;
        if(isset($this->lines[$line])){
            $pk = new SetScorePacket();
            $pk->type = $pk::TYPE_REMOVE;
            $pk->entries[] = $entry;
            $this->owner->sendDataPacket($pk);
        }
        $pk = new SetScorePacket();
        $pk->type = $pk::TYPE_CHANGE;
        $pk->entries[] = $entry;
        $this->owner->sendDataPacket($pk);
        $this->lines[$line] = $message;
    }

    /**
     * @param int $line
     */
    public function removeLine(int $line): void
    {
        $pk = new SetScorePacket();
        $pk->type = SetScorePacket::TYPE_REMOVE;
        $entry = new ScorePacketEntry();
        $entry->objectiveName = $this->owner->getName();
        $entry->score = $line;
        $entry->scoreboardId = $line;
        $pk->entries[] = $entry;
        $this->owner->sendDataPacket($pk);
        unset($this->lines[$line]);
    }

    /**
     * @param int $line
     *
     * @return string|null
     */
    public function getLine(int $line): ?string {
        return isset($this->lines[$line]) ? $line : null;
    }

    /**
     * @return string[]
     */
    public function getLines(): array
    {
        return $this->lines;
    }
}