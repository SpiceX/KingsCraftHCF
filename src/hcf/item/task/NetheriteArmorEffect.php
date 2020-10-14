<?php


namespace hcf\item\task;


use hcf\HCF;
use hcf\item\ItemIds;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\scheduler\Task;

class NetheriteArmorEffect extends Task
{
    /** @var HCF */
    private $plugin;

    /**
     * NetheriteArmorEffect constructor.
     * @param HCF $plugin
     */
    public function __construct(HCF $plugin)
    {
        $this->plugin = $plugin;
    }

    public function onRun(int $currentTick)
    {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
            $armorInventory = $player->getArmorInventory();
            if ($armorInventory->getHelmet()->getId() === ItemIds::NETHERITE_HELMET &&
                $armorInventory->getChestplate()->getId() === ItemIds::NETHERITE_CHESTPLATE &&
                $armorInventory->getLeggings()->getId() === ItemIds::NETHERITE_LEGGINGS &&
                $armorInventory->getBoots()->getId() === ItemIds::NETHERITE_BOOTS) {
                $player->addEffect(new EffectInstance(Effect::getEffect(Effect::DAMAGE_RESISTANCE), 40, 3));
                $player->addEffect(new EffectInstance(Effect::getEffect(Effect::WEAKNESS), 40, 2));
                $player->addEffect(new EffectInstance(Effect::getEffect(Effect::FIRE_RESISTANCE), 40));
            }
        }
    }
}