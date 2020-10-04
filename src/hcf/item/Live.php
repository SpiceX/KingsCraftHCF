<?php


namespace hcf\item;


use hcf\HCFPlayer;
use pocketmine\entity\Living;
use pocketmine\item\Food;
use pocketmine\utils\TextFormat;

class Live extends Food
{
    public function __construct(int $meta = 0)
    {
        parent::__construct(self::APPLE, $meta, "Apple");
        $this->getNamedTag()->setString("SpecialFeature", "Live");
        $customName = TextFormat::RESET . TextFormat::AQUA . TextFormat::BOLD . "Live";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Obtain an extra life.";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "<3";
        $this->setCustomName($customName);
        $this->setLore($lore);
    }

    public function onConsume(Living $consumer): void
    {
        if ($consumer instanceof HCFPlayer && $this->getNamedTag()->hasTag("SpecialFeature")) {
            $consumer->addLives(1);
            $consumer->sendMessage("§aNow you have an extra life!");
        }
    }

    public function getFoodRestore(): int
    {
        return 4;
    }

    public function getSaturationRestore(): float
    {
        return 2.4;
    }

    public function requiresHunger(): bool
    {
        return false;
    }
}