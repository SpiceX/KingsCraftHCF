<?php

namespace hcf\item;

use hcf\HCF;
use hcf\HCFPlayer;
use hcf\task\InvisibilityTask;
use hcf\task\SpecialItemCooldown;
use hcf\util\Utils;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\item\Food;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\level\particle\InkParticle;
use pocketmine\level\particle\RedstoneParticle;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\utils\TextFormat;

class InvisibilitySak extends Food
{

    public function __construct(int $meta = 0)
    {
        parent::__construct(ItemIds::DYE, $meta, "Ink Sak");
        $this->getNamedTag()->setString("SpecialFeature", "Vanish");
        $customName = TextFormat::RESET . TextFormat::AQUA . TextFormat::BOLD . "Vanish Sak";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Use this item to get vanish for 15 seconds.";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "You will disappear from your enemy with this item.";
        $this->setCustomName($customName);
        $this->setLore($lore);
    }

    public function requiresHunger(): bool
    {
        return false;
    }

    public function getFoodRestore(): int
    {
        return 0;
    }

    public function getSaturationRestore(): float
    {
        return 0.0;
    }

    public function onConsume(Living $consumer): void
    {
        if ($consumer instanceof HCFPlayer) {
            if ($consumer->hasInvisibilitySakCooldown){
                $consumer->sendMessage("§cThis item is on cooldown.");
                return;
            }
            $consumer->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_EATING, true);
            $consumer->getLevel()->broadcastLevelSoundEvent($consumer->asVector3(), LevelSoundEventPacket::SOUND_EAT);
            HCF::getInstance()->getScheduler()->scheduleRepeatingTask(new InvisibilityTask($consumer), 20);
            for ($i = 0; $i < 150; ++$i) {
                $vector = Utils::getRandomVector()->multiply(1.5);
                $consumer->getLevel()->addParticle(new InkParticle($consumer->getLocation()->add($vector->x, $vector->y, $vector->z)));
                $consumer->getLocation()->add($vector->x, $vector->y, $vector->z);
            }
            HCF::getInstance()->getScheduler()->scheduleRepeatingTask(new SpecialItemCooldown($consumer, 'InvisibilitySak'), 20);
        }
    }

    public function getAdditionalEffects(): array
    {
        return [
            new EffectInstance(Effect::getEffect(Effect::STRENGTH), 1200, 2),
        ];
    }

    public function getResidue(): Item
    {
        return Item::get(Item::AIR);
    }
}