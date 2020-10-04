<?php

namespace hcf\item;

use Exception;
use hcf\HCFPlayer;
use hcf\util\Utils;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\item\Food;
use pocketmine\item\Item;
use pocketmine\level\particle\RedstoneParticle;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\utils\TextFormat;

class EdibleNetherStar extends Food
{

    public function __construct(int $meta = 0){
        parent::__construct(self::NETHER_STAR, $meta, "Nether Star");
        $this->getNamedTag()->setString("SpecialFeature", "StrengthStar");
        $customName = TextFormat::RESET . TextFormat::AQUA . TextFormat::BOLD . "Strength Star";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Consume this star to get instant strength II.";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "The effect will disappear in 1 minute.";
        $this->setCustomName($customName);
        $this->setLore($lore);
    }

    public function requiresHunger() : bool{
        return false;
    }

    public function getFoodRestore() : int{
        return 0;
    }

    public function getSaturationRestore() : float{
        return 0.0;
    }

    public function onConsume(Living $consumer): void
    {
        if ($consumer instanceof HCFPlayer){
            $consumer->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_EATING, true);
            $consumer->getLevel()->broadcastLevelSoundEvent($consumer->asVector3(), LevelSoundEventPacket::SOUND_EAT);
            for ($i = 0; $i < 150; ++$i) {
                $vector = Utils::getRandomVector()->multiply(1.5);
                $consumer->getLevel()->addParticle(new RedstoneParticle($consumer->getLocation()->add($vector->x, $vector->y, $vector->z)));
                $consumer->getLocation()->add($vector->x, $vector->y, $vector->z);
            }
        }
    }

    /**
     * @return EffectInstance[]
     * @throws Exception
     */
    public function getAdditionalEffects() : array{
        return [
            new EffectInstance(Effect::getEffect(Effect::STRENGTH), 1200, random_int(1,2)),
        ];
    }

    public function getResidue(): Item
    {
        return Item::get(Item::AIR);
    }
}