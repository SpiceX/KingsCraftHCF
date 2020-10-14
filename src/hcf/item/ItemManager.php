<?php

namespace hcf\item;

use hcf\HCF;
use hcf\item\armor\NetheriteBoots;
use hcf\item\armor\NetheriteChestplate;
use hcf\item\armor\NetheriteHelmet;
use hcf\item\armor\NetheriteLeggings;
use hcf\item\entity\Egg;
use hcf\item\entity\EnderPearl;
use hcf\item\entity\FireworksRocket;
use hcf\item\entity\FishingHook;
use hcf\item\entity\GrapplingHook;
use hcf\item\task\NetheriteArmorEffect;
use hcf\item\tool\NetheriteAxe;
use hcf\item\tool\NetheriteHoe;
use hcf\item\tool\NetheritePickaxe;
use hcf\item\tool\NetheriteShovel;
use hcf\item\types\GlassBottle;
use hcf\item\types\SplashPotion;
use hcf\item\types\TeleportationBall;
use pocketmine\entity\Entity;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\ProtectionEnchantment;
use pocketmine\item\enchantment\SharpnessEnchantment;
use pocketmine\item\GoldBoots;
use pocketmine\item\GoldChestplate;
use pocketmine\item\GoldHelmet;
use pocketmine\item\GoldLeggings;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\Server;

class ItemManager
{

    /** @var HCF */
    private $core;

    /**
     * ItemManager constructor.
     *
     * @param HCF $core
     */
    public function __construct(HCF $core)
    {
        $this->core = $core;
        $core->getServer()->getPluginManager()->registerEvents(new ItemListener($core), $core);
        $core->getScheduler()->scheduleRepeatingTask(new NetheriteArmorEffect($core), 20);
        $this->init();
    }

    public function init(): void
    {
        Enchantment::registerEnchantment(new Enchantment(Enchantment::LOOTING, "Looting", Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_SWORD, Enchantment::SLOT_NONE, 3));
        Enchantment::registerEnchantment(new Enchantment(Enchantment::FORTUNE, "Fortune", Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_DIG, Enchantment::SLOT_NONE, 3));
        Enchantment::registerEnchantment(new Enchantment(Enchantment::POWER, "%enchantment.arrowDamage", Enchantment::RARITY_COMMON, Enchantment::SLOT_BOW, Enchantment::SLOT_NONE, HCF::MAX_POWER));
        Enchantment::registerEnchantment(new ProtectionEnchantment(Enchantment::PROTECTION, "%enchantment.protect.all", Enchantment::RARITY_COMMON, Enchantment::SLOT_ARMOR, Enchantment::SLOT_NONE, HCF::MAX_PROTECTION, 0.75, null));
        Enchantment::registerEnchantment(new SharpnessEnchantment(Enchantment::SHARPNESS, "%enchantment.damage.all", Enchantment::RARITY_COMMON, Enchantment::SLOT_SWORD, Enchantment::SLOT_AXE, HCF::MAX_SHARPNESS));
        ItemFactory::registerItem(
            new class extends GoldHelmet {

                /**
                 * @return int
                 */
                public function getMaxDurability(): int
                {
                    return parent::getMaxDurability() * 2;
                }
            }, true
        );
        ItemFactory::registerItem(
            new class extends GoldChestplate {

                /**
                 * @return int
                 */
                public function getMaxDurability(): int
                {
                    return parent::getMaxDurability() * 2;
                }
            }, true
        );
        ItemFactory::registerItem(
            new class extends GoldLeggings {

                /**
                 * @return int
                 */
                public function getMaxDurability(): int
                {
                    return parent::getMaxDurability() * 2;
                }
            }, true
        );
        ItemFactory::registerItem(
            new class extends GoldBoots {

                /**
                 * @return int
                 */
                public function getMaxDurability(): int
                {
                    return parent::getMaxDurability() * 2;
                }
            }, true
        );
        ItemFactory::registerItem(new types\GrapplingHook(), true);
        ItemFactory::registerItem(new TeleportationBall(), true);
        ItemFactory::registerItem(new SplashPotion(), true);
        ItemFactory::registerItem(new GlassBottle(), true);
        Entity::registerEntity(\hcf\item\entity\TeleportationBall::class, true);
        Entity::registerEntity(GrapplingHook::class);
        ItemFactory::registerItem(new Fireworks());
        ItemFactory::registerItem(new AntiTrapper(), true);
        ItemFactory::registerItem(new InvisibilitySak(), true);
        ItemFactory::registerItem(new Live(), true);
        ItemFactory::registerItem(new FishingRod(), true);
        ItemFactory::registerItem(new LumberAxe(), true);
        ItemFactory::registerItem(new EdibleNetherStar(), true);
        ItemFactory::registerItem(new NetheriteBoots(), true);
        ItemFactory::registerItem(new NetheriteLeggings(), true);
        ItemFactory::registerItem(new NetheriteChestplate(), true);
        ItemFactory::registerItem(new NetheriteHelmet(), true);
        ItemFactory::registerItem(new NetheriteAxe(), true);
        ItemFactory::registerItem(new NetheriteHoe(), true);
        ItemFactory::registerItem(new NetheritePickaxe(), true);
        ItemFactory::registerItem(new NetheriteShovel(), true);
        //ItemFactory::registerItem(new NetheriteSword(), true);
        //ItemFactory::registerItem(new \hcf\item\EnderPearl(), true);
        Item::initCreativeItems();
        if (!Entity::registerEntity(FireworksRocket::class, false, ["FireworksRocket"])) {
            Server::getInstance()->getLogger()->error("Failed to register FireworksRocket entity with savename 'FireworksRocket'");
        }
        Entity::registerEntity(FishingHook::class, true);
        Entity::registerEntity(Egg::class, true);
        Entity::registerEntity(EnderPearl::class, true, ['EnderPearl']);
    }

    /**
     * @return HCF
     */
    public function getCore(): HCF
    {
        return $this->core;
    }
}