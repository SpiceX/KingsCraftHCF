<?php

namespace hcf\item;

use Exception;
use hcf\HCF;
use hcf\HCFPlayer;
use hcf\task\SpecialItemCooldown;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class Fireworks extends Item {

    public const TYPE_SMALL_SPHERE = 0;
    public const TYPE_HUGE_SPHERE = 1;
    public const TYPE_STAR = 2;
    public const TYPE_CREEPER_HEAD = 3;
    public const TYPE_BURST = 4;

    public const COLOR_BLACK = "\x00";
    public const COLOR_RED = "\x01";
    public const COLOR_DARK_GREEN = "\x02";
    public const COLOR_BROWN = "\x03";
    public const COLOR_BLUE = "\x04";
    public const COLOR_DARK_PURPLE = "\x05";
    public const COLOR_DARK_AQUA = "\x06";
    public const COLOR_GRAY = "\x07";
    public const COLOR_DARK_GRAY = "\x08";
    public const COLOR_PINK = "\x09";
    public const COLOR_GREEN = "\x0a";
    public const COLOR_YELLOW = "\x0b";
    public const COLOR_LIGHT_AQUA = "\x0c";
    public const COLOR_DARK_PINK = "\x0d";
    public const COLOR_GOLD = "\x0e";
    public const COLOR_WHITE = "\x0f";

    public function __construct(int $meta = 0) {
        parent::__construct(self::FIREWORKS, $meta, "Fireworks");
        $this->getNamedTag()->setString("SpecialFeature", "HighImpulse");
        $customName = TextFormat::RESET . TextFormat::AQUA . TextFormat::BOLD . "ImpulseWorks";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Use this item to get a very high impulse.";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "You will not get fall damage.";
        $this->setCustomName($customName);
        $this->setLore($lore);
    }

    public function getFlightDuration(): int {
        return $this->getExplosionsTag()->getByte("Flight", 1);
    }

    /**
     * @return int
     * @throws Exception
     */
    public function getRandomizedFlightDuration(): int {
        return ($this->getFlightDuration() + 1) * 10 + random_int(0, 5) + random_int(0, 6);
    }

    public function setFlightDuration(int $duration): void {
        $tag = $this->getExplosionsTag();
        $tag->setByte("Flight", $duration);
        $this->setNamedTagEntry($tag);
    }

    public function addExplosion(int $type, string $color, string $fade = "", bool $flicker = false, bool $trail = false): void
    {
        $explosion = new CompoundTag();
        $explosion->setByte("FireworkType", $type);
        $explosion->setByteArray("FireworkColor", $color);
        $explosion->setByteArray("FireworkFade", $fade);
        $explosion->setByte("FireworkFlicker", $flicker ? 1 : 0);
        $explosion->setByte("FireworkTrail", $trail ? 1 : 0);

        $tag = $this->getExplosionsTag();
        $explosions = $tag->getListTag("Explosions") ?? new ListTag("Explosions");
        $explosions->push($explosion);
        $tag->setTag($explosions);
        $this->setNamedTagEntry($tag);
    }

    protected function getExplosionsTag(): CompoundTag {
        return $this->getNamedTag()->getCompoundTag("Fireworks") ?? new CompoundTag("Fireworks");
    }

    public function onActivate(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector): bool {
        if ($player instanceof HCFPlayer){
            if ($player->hasFireworksCooldown){
                $player->sendMessage("§cThis item is on cooldown.");
                return false;
            }
        }

        $nbt = Entity::createBaseNBT($blockReplace->add(0.5, 0, 0.5), new Vector3(0.001, 0.05, 0.001), lcg_value() * 360, 90);
        $entity = Entity::createEntity("FireworksRocket", $player->getLevel(), $nbt, $this);
        HCF::getInstance()->getScheduler()->scheduleRepeatingTask(new SpecialItemCooldown($player, 'Fireworks'), 20);

        if($entity instanceof Entity) {
            --$this->count;
            $entity->spawnToAll();
            $player->setMotion($player->getDirectionVector()->multiply(0.9)->add(0, 2.5, 0));
            return true;
        }
        return false;
    }
}