<?php

namespace hcf\koth;

use hcf\crate\Crate;
use hcf\HCF;
use hcf\HCFPlayer;
use hcf\item\types\CrateKey;
use hcf\translation\Translation;
use hcf\translation\TranslationException;
use pocketmine\level\Position;
use pocketmine\utils\TextFormat;

class KOTHArena
{

    /** @var string */
    private $name;

    /** @var Position */
    private $firstPosition;

    /** @var Position */
    private $secondPosition;

    /** @var null|HCFPlayer */
    private $capturer;

    /** @var int */
    private $captureProgress = 0;

    /** @var int */
    private $objectiveTime;

    /**
     * KOTHArena constructor.
     *
     * @param string $name
     * @param Position $firstPosition
     * @param Position $secondPosition
     * @param int $objectiveTime
     *
     * @throws KOTHException
     */
    public function __construct(string $name, Position $firstPosition, Position $secondPosition, int $objectiveTime)
    {
        $this->name = $name;
        $this->firstPosition = $firstPosition;
        $this->secondPosition = $secondPosition;
        if ($firstPosition->getLevel() === null || $secondPosition->getLevel() === null) {
            throw new KOTHException("KOTH arena \"$name\" position levels are invalid.");
        }
        if ($firstPosition->getLevel()->getName() !== $secondPosition->getLevel()->getName()) {
            throw new KOTHException("KOTH arena \"$name\" position levels are not the same.");
        }
        $this->objectiveTime = $objectiveTime;
    }

    /**
     * @throws TranslationException
     */
    public function tick(): void
    {
        if ($this->captureProgress >= $this->objectiveTime) {
            if (!$this->capturer->isOnline()) {
                $this->captureProgress = 0;
                $this->capturer = null;
                return;
            }
            $key = (new CrateKey(HCF::getInstance()->getCrateManager()->getCrate(Crate::UNKNOWN)))->setCount(ceil($this->objectiveTime / 300));
            if ($this->capturer->getInventory()->canAddItem($key)) {
                $this->capturer->getInventory()->addItem($key);
            } else {
                $this->capturer->getLevel()->dropItem($this->capturer->asVector3(), $key);
            }

            HCF::getInstance()->getKOTHManager()->endGame();
            HCF::getInstance()->getServer()->broadcastMessage(Translation::getMessage("kothEnd", [
                "player" => TextFormat::YELLOW . $this->capturer->getName(),
                "name" => TextFormat::LIGHT_PURPLE . $this->name
            ]));
            /*$webHook = new Webhook("https://discordapp.com/api/webhooks/721276981349580912/zKe8vVFlBgoXoVj74JrjpDRt5_VEUDsvUH_WkeYoLA_1ik8690UzCykk4gHis9YYp-M7");
            $msg = new Message();
            $embed = new Embed();
            $embed->setTitle("KingsHCF | KoTH");
            $embed->setColor(0x00FF00);
            $embed->setDescription($this->capturer->getName() . " was won " . $this->name . " KoTH GG.");
            $msg->addEmbed($embed);
            $webHook->send($msg);*/
        }
        if ($this->capturer === null || (!$this->isPositionInside($this->capturer)) || (!$this->capturer->isOnline())) {
            $this->captureProgress = 0;
            $this->capturer = null;
            foreach ($this->firstPosition->getLevel()->getPlayers() as $player) {
                if (!$player instanceof HCFPlayer) {
                    continue;
                }
                if ($this->isPositionInside($player) && $player->isInvincible() === false) {
                    if ($this->capturer !== null) {
                        return;
                    }
                    $this->capturer = $player;
                }
            }
            if ($this->capturer !== null) {
                HCF::getInstance()->getServer()->broadcastMessage(Translation::getMessage("kothCurrentCapturer", [
                    "player" => TextFormat::YELLOW . $this->capturer->getName(),
                    "name" => TextFormat::LIGHT_PURPLE . $this->name
                ]));
                /*$webHook = new Webhook("https://discordapp.com/api/webhooks/721276981349580912/zKe8vVFlBgoXoVj74JrjpDRt5_VEUDsvUH_WkeYoLA_1ik8690UzCykk4gHis9YYp-M7");
                $msg = new Message();
                $embed = new Embed();
                $embed->setTitle("KingsHCF | KoTH");
                $embed->setColor(0x00FF00);
                $embed->setDescription($this->capturer->getName() . " is now capturing " . $this->name . " KoTH.");
                $msg->addEmbed($embed);
                $webHook->send($msg);*/
            }
        }
        $this->captureProgress++;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param HCFPlayer|null $player
     */
    public function setCapturer(?HCFPlayer $player = null): void
    {
        $this->capturer = $player;
    }

    /**
     * @return HCFPlayer|null
     */
    public function getCapturer(): ?HCFPlayer
    {
        return $this->capturer;
    }

    /**
     * @param int $amount
     */
    public function setCaptureProgress(int $amount): void
    {
        $this->captureProgress = $amount;
    }

    /**
     * @return int
     */
    public function getCaptureProgress(): int
    {
        return $this->captureProgress;
    }

    /**
     * @return int
     */
    public function getObjectiveTime(): int
    {
        return $this->objectiveTime;
    }

    /**
     * @return Position
     */
    public function getFirstPosition(): Position
    {
        return $this->firstPosition;
    }

    /**
     * @return Position
     */
    public function getSecondPosition(): Position
    {
        return $this->secondPosition;
    }

    /**
     * @param Position $position
     *
     * @return bool
     */
    public function isPositionInside(Position $position): bool
    {
        $level = $position->getLevel();
        $firstPosition = $this->firstPosition;
        $secondPosition = $this->secondPosition;
        $minX = min($firstPosition->getX(), $secondPosition->getX());
        $maxX = max($firstPosition->getX(), $secondPosition->getX());
        $minZ = min($firstPosition->getZ(), $secondPosition->getZ());
        $maxZ = max($firstPosition->getZ(), $secondPosition->getZ());
        return $minX <= $position->getX() and $maxX >= $position->getFloorX() and
            $minZ <= $position->getZ() and $maxZ >= $position->getFloorZ() and
            $this->firstPosition->getLevel()->getName() === $level->getName();
    }

}