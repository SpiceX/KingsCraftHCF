<?php

namespace hcf\groups;

use hcf\HCF;
use pocketmine\utils\TextFormat;

class GroupManager implements GroupIdentifiers {

    /** @var HCF */
    private $core;

    /** @var Group[] */
    private $groups = [];

    /**
     * GroupManager constructor.
     *
     * @param HCF $core
     */
    public function __construct(HCF $core) {
        $this->core = $core;
        $this->init();
    }

    public function init(): void {
        $this->addGroup(new Group("Player", TextFormat::GOLD . TextFormat::BOLD . "PLAYER", self::PLAYER, 1800,
            TextFormat::GOLD . "⚔ " . TextFormat::DARK_AQUA . "{faction_rank}{faction} ". TextFormat::GOLD . TextFormat::BOLD . "PLAYER" . TextFormat::RESET . TextFormat::WHITE . " {player}" . TextFormat::GRAY . ": {message}",
            TextFormat::DARK_AQUA . "{faction_rank}{faction} ". TextFormat::GOLD . TextFormat::BOLD . "Player" . TextFormat::RESET . TextFormat::WHITE . " {player}", [
                "kit.food",
                "kit.diamond",
                "kit.starter"
            ]));
        $this->addGroup(new Group("Astro", TextFormat::YELLOW . TextFormat::BOLD . "ASTRO", self::ASTRO, 1500,
            TextFormat::YELLOW . "⚔ " . TextFormat::DARK_AQUA . "{faction_rank}{faction} ". TextFormat::YELLOW . TextFormat::BOLD . "ASTRO" . TextFormat::RESET . TextFormat::WHITE . " {player}" . TextFormat::GRAY . ": " . TextFormat::WHITE . "{message}",
            TextFormat::DARK_AQUA . "{faction_rank}{faction} ". TextFormat::YELLOW . TextFormat::BOLD . "ASTRO" . TextFormat::RESET . TextFormat::WHITE . " {player}", [
                "kit.food",
                "kit.starter",
                "kit.astro"
            ]));
        $this->addGroup(new Group("Legend", TextFormat::BLUE . TextFormat::BOLD . "LEGEND", self::LEGEND, 1200,
            TextFormat::BLUE . "⚔ " . TextFormat::DARK_AQUA . "{faction_rank}{faction} ". TextFormat::BLUE . TextFormat::BOLD . "LEGEND" . TextFormat::RESET . TextFormat::WHITE . " {player}" . TextFormat::GRAY . ": " . TextFormat::WHITE . "{message}",
            TextFormat::DARK_AQUA . "{faction_rank}{faction} ". TextFormat::BLUE . TextFormat::BOLD . "LEGEND" . TextFormat::RESET . TextFormat::WHITE . " {player}", [
                "kit.food",
                "kit.starter",
                "kit.astro",
                "kit.legend"
            ]));
        $this->addGroup(new Group("Revenant", TextFormat::DARK_RED . TextFormat::BOLD . "REVENANT", self::REVENANT, 900,
            TextFormat::DARK_RED . "⚔ " . TextFormat::DARK_AQUA . "{faction_rank}{faction} ". TextFormat::DARK_RED . TextFormat::BOLD . "REVENANT" . TextFormat::RESET . TextFormat::WHITE . " {player}" . TextFormat::GRAY . ": " . TextFormat::WHITE . "{message}",
            TextFormat::DARK_AQUA . "{faction_rank}{faction} ". TextFormat::DARK_RED . TextFormat::BOLD . "Revenant" . TextFormat::RESET . TextFormat::WHITE . " {player}", [
                "kit.food",
                "kit.starter",
                "kit.astro",
                "kit.legend",
                "kit.revenant"
            ]));
        $this->addGroup(new Group("King", TextFormat::RED . TextFormat::BOLD . "King", self::KING, 600,
            TextFormat::RED . "⚔ " . TextFormat::DARK_AQUA . "{faction_rank}{faction} ". TextFormat::BLUE . TextFormat::BOLD . "King" . TextFormat::RESET . TextFormat::WHITE . " {player}" . TextFormat::GRAY . ": " . TextFormat::WHITE . "{message}",
            TextFormat::DARK_AQUA . "{faction_rank}{faction} ". TextFormat::BLUE . TextFormat::BOLD . "King" . TextFormat::RESET . TextFormat::WHITE . " {player}", [
                "kit.food",
                "kit.starter",
                "kit.astro",
                "kit.legend",
                "kit.revenant",
                "kit.king"
            ]));
    }

    /**
     * @param int $identifier
     *
     * @return Group|null
     */
    public function getGroupByIdentifier(int $identifier): ?Group {
        return $this->groups[$identifier] ?? null;
    }

    /**
     * @return Group[]
     */
    public function getGroups(): array {
        return $this->groups;
    }

    /**
     * @param string $name
     *
     * @return Group
     */
    public function getGroupByName(string $name): ?Group {
        foreach($this->groups as $group) {
            if($group->getName() === $name) {
                return $group;
            }
        }
        return null;
    }

    /**
     * @param Group $group
     */
    public function addGroup(Group $group): void {
        $this->groups[$group->getIdentifier()] = $group;
    }

    /**
     * @return HCF
     */
    public function getCore(): HCF
    {
        return $this->core;
    }
}