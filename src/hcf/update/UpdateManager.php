<?php

namespace hcf\update;

use hcf\HCF;
use hcf\HCFPlayer;
use hcf\update\task\UpdateTask;
use hcf\util\Padding;
use libs\utils\Utils;
use libs\utils\UtilsException;
use pocketmine\utils\TextFormat;

class UpdateManager
{

    /** @var HCF */
    private $core;

    /**
     * UpdateManager constructor.
     *
     * @param HCF $core
     */
    public function __construct(HCF $core)
    {
        $this->core = $core;
        $this->core->getScheduler()->scheduleRepeatingTask(new UpdateTask($core), 20);
        $core->getServer()->getPluginManager()->registerEvents(new UpdateListener($core), $core);
    }

    public function updateBossbar(HCFPlayer $player): void
    {
        if ($player->isClosed() || $player->getClass() === HCFPlayer::BARD) {
            return;
        }
        if ($player->isOnline()) {
            $bossbar = $player->getBossBar();
            $bossbar->update(Padding::centerText(
                $this->core->getConfig()->get('bossbar_title', "§l§b» §9Kings§fHCF §b«" . "\n\n§r") .
                "§fName: §9" . $player->getName() . "  " .
                "§fCPS: §9" . $this->core->getCpsCounter()->getCps($player) . "  " .
                "§fPing: §9" . $player->getPing() . "  " .
                "§fVictim: §9"
            ), 1
            );
        }
    }

    /**
     * @param HCFPlayer $player
     *
     * @throws UtilsException
     */
    public function updateScoreboard(HCFPlayer $player): void
    {
        if ($player->isOnline()) {
            $scoreboard = $player->getScoreboard();
            if (!$scoreboard->isSpawned()) {
                $scoreboard->spawn($this->core->getConfig()->get('scoreboard_title', '§9§lKings§fHCF'));
                $scoreboard->setScoreLine(1, "§7---------------------");
                $scoreboard->setScoreLine(2, TextFormat::YELLOW . " Direction: " . TextFormat::RESET . TextFormat::WHITE . Utils::getCompassDirection($player->getYaw() - 90));
                $time = (30 - (time() - $player->getCombatTagTime())) > 0 ? 30 - (time() - $player->getCombatTagTime()) : 0;
                if ($time > 0) {
                    $scoreboard->setScoreLine(3, TextFormat::DARK_PURPLE . "- Combat Tag: " . TextFormat::RESET . TextFormat::WHITE . $time . "s");
                }
                return;
            }
            if (($time = (30 - (time() - $player->getCombatTagTime()))) > 0) {
                $scoreboard->setScoreLine(3, TextFormat::DARK_PURPLE . "- Combat Tag: " . TextFormat::RESET . TextFormat::WHITE . $time . "s");
            } else {
                if ($scoreboard->getLine(3) !== null) {
                    $scoreboard->removeLine(3);
                }
            }
            if ($player->isInvincible()) {
                if ($player->canDeductInvincibilityTime()) {
                    $player->subtractInvincibilityTime();
                    $time = $player->getInvincibilityTime();
                    $minutes = floor($time / 60);
                    $seconds = $time % 60;
                    if ($seconds < 10) {
                        $seconds = "0$seconds";
                    }
                    if ($seconds === 0) {
                        $player->setInvincible($time);
                    }
                    $scoreboard->setScoreLine(4, TextFormat::DARK_GREEN . "- Invincibility: " . TextFormat::RESET . TextFormat::WHITE . "$minutes:$seconds");
                }
            } else {
                if ($scoreboard->getLine(4) !== null) {
                    $scoreboard->removeLine(4);
                }
            }
            if ($player->getClass() !== null) {
                $scoreboard->setScoreLine(5, TextFormat::DARK_RED . "- Class: " . TextFormat::RESET . TextFormat::WHITE . $player->getClass());
            } elseif ($player->getClass() === null) {
                if ($scoreboard->getLine(5) !== null) {
                    $scoreboard->removeLine(5);
                }
            }
            if (($time = 10 - (time() - $player->getEnderPearlTime())) > 0) {
                $scoreboard->setScoreLine(6, TextFormat::LIGHT_PURPLE . "- Ender Pearl: " . TextFormat::RESET . TextFormat::WHITE . $time . "s");
            } else {
                if ($scoreboard->getLine(6) !== null) {
                    $scoreboard->removeLine(6);
                }
            }
            if (($time = 30 - (time() - $player->getBuffDelayTime())) > 0) {
                $scoreboard->setScoreLine(7, TextFormat::AQUA . "- Bard Cooldown: " . TextFormat::RESET . TextFormat::WHITE . $time . "s");
            } else {
                if ($scoreboard->getLine(7) !== null) {
                    $scoreboard->removeLine(7);
                }
            }
            if (($game = $this->core->getKOTHManager()->getGame()) !== null) {
                $time = $game->getObjectiveTime();
                if ($game->getCapturer() !== null) {
                    $time -= $game->getCaptureProgress();
                }
                $minutes = floor($time / 60);
                $seconds = $time % 60;
                if ($seconds < 10) {
                    $seconds = "0$seconds";
                }
                $scoreboard->setScoreLine(8, TextFormat::BLUE . "- {$game->getName()}: " . TextFormat::RESET . TextFormat::WHITE . "$minutes:$seconds");
            } else {
                if ($scoreboard->getLine(8) !== null) {
                    $scoreboard->removeLine(8);
                }
            }
            if ($this->core->isEndOfTheWorld() === true) {
                if ($scoreboard->getLine(9) === null) {
                    $scoreboard->setScoreLine(9, TextFormat::DARK_RED . "- EOTW HAS BEGUN");
                }
            } else {
                if ($scoreboard->getLine(9) !== null) {
                    $scoreboard->removeLine(9);
                }
            }
            if ($this->core->isStartOfTheWorld() === true) {
                $time = 3600 - (time() - $this->core->getStartOfTheWorld());
                $minutes = floor($time / 60);
                $seconds = $time % 60;
                if ($seconds < 10) {
                    $seconds = "0$seconds";
                }
                $scoreboard->setScoreLine(9, TextFormat::DARK_RED . "- SOTW: " . TextFormat::RESET . TextFormat::WHITE . "$minutes:$seconds");
            }
            $scoreboard->setScoreLine(2, TextFormat::BLUE . "- Direction: " . TextFormat::RESET . TextFormat::WHITE . Utils::getCompassDirection($player->getYaw() - 90));
            if ($this->core->getCombatManager()->getCombatListener()->hasGoldenAppleCooldown($player)) {
                $scoreboard->setScoreLine(10, TextFormat::LIGHT_PURPLE . "- GoldenApple: " . TextFormat::RESET . TextFormat::WHITE . $this->core->getCombatManager()->getCombatListener()->getGoldenAppleCooldown($player) . "s");
            } else {
                if ($scoreboard->getLine(10) !== null) {
                    $scoreboard->removeLine(10);
                }
            }
            if ($this->core->getCombatManager()->getCombatListener()->hasGodAppleCooldown($player)) {
                $scoreboard->setScoreLine(11, TextFormat::GOLD . "- GodApple: " . TextFormat::RESET . TextFormat::WHITE . $this->core->getCombatManager()->getCombatListener()->getGodAppleCooldown($player));
            } else {
                if ($scoreboard->getLine(11) !== null) {
                    $scoreboard->removeLine(11);
                }
            }
            $scoreboard->setScoreLine(12, "§7--------------------");
        }
    }
}