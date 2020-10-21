<?php

namespace hcf;

use hcf\announcement\AnnouncementManager;
use hcf\area\AreaException;
use hcf\area\AreaManager;
use hcf\combat\CombatManager;
use hcf\command\CommandManager;
use hcf\crate\CrateManager;
use hcf\discord\Logger;
use hcf\enchant\CustomEnchantManager;
use hcf\entity\EntityManager;
use hcf\faction\FactionManager;
use hcf\groups\GroupManager;
use hcf\item\ItemManager;
use hcf\kit\KitManager;
use hcf\koth\KOTHException;
use hcf\koth\KOTHManager;
use hcf\level\LevelManager;
use hcf\network\NetworkManager;
use hcf\provider\MySQLProvider;
use hcf\provider\YamlProvider;
use hcf\road\RoadManager;
use hcf\shop\ShopManager;
use hcf\translation\TranslationException;
use hcf\update\UpdateManager;
use hcf\util\CpsCounter;
use hcf\watchdog\WatchdogManager;
use hcf\wayPoint\WayPointManager;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\entity\Effect;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginException;
use pocketmine\utils\Color;
use pocketmine\utils\TextFormat as TF;
use ReflectionException;

class HCF extends PluginBase
{

    public const GAMEMODE = "Hardcore Factions";
    public const SERVER_NAME = TF::RESET . TF::BOLD . TF::BLUE . "Kings" . TF::WHITE . "HCF " . TF::RESET . TF::GRAY . self::GAMEMODE;
    public const BORDER = 2000;
    public const EDIT = 200;
    public const MAP = 1;
    public const MAX_PROTECTION = 3;
    public const MAX_POWER = 5;
    public const MAX_SHARPNESS = 5;

    /** @var BigEndianNBTStream */
    public static $nbtWriter;
    /** @var MySQLProvider */
    private $provider;
    /** @var LevelManager */
    private $levelManager;
    /** @var GroupManager */
    private $groupManager;
    /** @var CommandManager */
    private $commandManager;
    /** @var ShopManager */
    private $shopManager;
    /** @var AreaManager */
    private $areaManager;
    /** @var RoadManager */
    private $roadManager;
    /** @var UpdateManager */
    private $updateManager;
    /** @var CombatManager */
    private $combatManager;
    /** @var EntityManager */
    private $entityManager;
    /** @var AnnouncementManager */
    private $announcementManager;
    /** @var FactionManager */
    private $factionManager;
    /** @var ItemManager */
    private $itemManager;
    //   /** @var NetworkManager */
    //  private $networkManager;
    /** @var KitManager */
    private $kitManager;
    /** @var CrateManager */
    private $crateManager;
    /** @var WatchdogManager */
    private $watchdogManager;
    /** @var KOTHManager */
    private $kothManager;
    /** @var WayPointManager */
    private $wayPointManager;
    /** @var bool */
    private $eotw = false;
    /** @var int */
    private $sotw = 0;
    /** @var self */
    private static $instance;
    /** @var YamlProvider */
    private $yamlProvider;
    /** @var CpsCounter */
    private $cpsCounter;
    /** @var CustomEnchantManager */
    private $enchantmentManager;

    public function onLoad(): void
    {
        self::$nbtWriter = new BigEndianNBTStream();
        $this->getServer()->getNetwork()->setName(self::SERVER_NAME);
        self::$instance = $this;
    }

    /**
     * @throws ReflectionException
     * @throws AreaException
     * @throws KOTHException
     */
    public function onEnable(): void
    {
        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);
        }
        $this->provider = new MySQLProvider($this);
        $this->yamlProvider = new YamlProvider($this);
        $this->levelManager = new LevelManager($this);
        //$this->enchantmentManager = new CustomEnchantManager($this);
        $this->groupManager = new GroupManager($this);
        $this->commandManager = new CommandManager($this);
        $this->shopManager = new ShopManager($this);
        $this->areaManager = new AreaManager($this);
        $this->roadManager = new RoadManager($this);
        $this->updateManager = new UpdateManager($this);
        $this->combatManager = new CombatManager($this);
        $this->entityManager = new EntityManager($this);
        //$this->announcementManager = new AnnouncementManager($this); disabled announcements
        $this->factionManager = new FactionManager($this);
        $this->itemManager = new ItemManager($this);
        //     $this->networkManager = new NetworkManager($this);
        $this->kitManager = new KitManager($this);
        $this->kitManager->loadCustomKits();
        $this->cpsCounter = new CpsCounter($this);
        $this->crateManager = new CrateManager($this);
        $this->watchdogManager = new WatchdogManager($this);
        $this->kothManager = new KOTHManager($this);
        $this->wayPointManager = new WayPointManager($this);
        $this->getServer()->getPluginManager()->registerEvents(new HCFListener($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new Logger($this), $this);
        Effect::registerEffect(new Effect(28,'%potion.badOmen',new Color(0xce, 0xff, 0xff),true,120000, false));
    }

    public function onDisable(): void
    {
        $this->kitManager->saveCustomKits();
        $this->crateManager->saveCrates();
    }

    /**
     * @return bool
     */
    public function isEndOfTheWorld(): bool
    {
        return $this->eotw;
    }

    /**
     * @param bool $value
     *
     * @throws TranslationException
     */
    public function setEndOfTheWorld(bool $value = true): void
    {
        $this->eotw = $value;
        $this->kothManager->startEndOfTheWorldKOTH();
    }

    /**
     * @return bool
     */
    public function isStartOfTheWorld(): bool
    {
        return 3600 - (time() - $this->sotw) > 0;
    }

    /**
     * @return int
     */
    public function getStartOfTheWorld(): int
    {
        return $this->sotw;
    }

    public function setStartOfTheWorld(): void
    {
        $this->sotw = time();
        $this->kothManager->endGame();
    }

    /**
     * @param Item $item
     *
     * @return string
     */
    public static function encodeItem(Item $item): string
    {
        return self::$nbtWriter->write($item->nbtSerialize());
    }

    /**
     * @param string $compression
     *
     * @return Item
     *
     * @throws PluginException
     */
    public static function decodeItem(string $compression): Item
    {
        $tag = self::$nbtWriter->read($compression);
        if (!$tag instanceof CompoundTag) {
            throw new PluginException("Expected a CompoundTag, got " . get_class($tag));
        }
        return Item::nbtDeserialize($tag);
    }

    /**
     * @param Inventory $inventory
     * @return string
     */
    public static function encodeInventory(Inventory $inventory): string
    {
        $serializedItems = [];
        foreach ($inventory->getContents() as $item) {
            $serializedItems[] = $item->nbtSerialize();
        }

        return self::$nbtWriter->writeCompressed(new CompoundTag("Content", [new ListTag("Items", $serializedItems)]));
    }

    /**
     * @param Item[] $items
     * @return string
     */
    public static function encodeItemList(array $items): string
    {
        $serializedItems = [];
        foreach ($items as $item) {
            $serializedItems[] = $item->nbtSerialize();
        }

        return self::$nbtWriter->writeCompressed(new CompoundTag("Content", [new ListTag("Items", $serializedItems)]));
    }

    /**
     * @param string $compression
     *
     * @return Item[]
     */
    public static function decodeInventory(string $compression): array
    {
        if (empty($compression)) {
            return [];
        }

        $tag = self::$nbtWriter->readCompressed($compression);
        if (!$tag instanceof CompoundTag) {
            throw new PluginException("Expected a CompoundTag, got " . get_class($tag));
        }
        $content = [];
        /** @var CompoundTag $item */
        foreach ($tag->getListTag("Items")->getValue() as $item) {
            $content[] = Item::nbtDeserialize($item);
        }
        return $content;
    }

    /**
     * @return HCF
     */
    public static function getInstance(): self
    {
        return self::$instance;
    }

    /**
     * @return LevelManager
     */
    public function getLevelManager(): LevelManager
    {
        return $this->levelManager;
    }

    /**
     * @return MySQLProvider
     */
    public function getMySQLProvider(): MySQLProvider
    {
        return $this->provider;
    }

    /**
     * @return GroupManager
     */
    public function getGroupManager(): GroupManager
    {
        return $this->groupManager;
    }

    /**
     * @return CommandManager
     */
    public function getCommandManager(): CommandManager
    {
        return $this->commandManager;
    }

    /**
     * @return ShopManager
     */
    public function getShopManager(): ShopManager
    {
        return $this->shopManager;
    }

    /**
     * @return AreaManager
     */
    public function getAreaManager(): AreaManager
    {
        return $this->areaManager;
    }

    /**
     * @return RoadManager
     */
    public function getRoadManager(): RoadManager
    {
        return $this->roadManager;
    }

    /**
     * @return UpdateManager
     */
    public function getUpdateManager(): UpdateManager
    {
        return $this->updateManager;
    }

    /**
     * @return CombatManager
     */
    public function getCombatManager(): CombatManager
    {
        return $this->combatManager;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }

    /**
     * @return AnnouncementManager
     */
    public function getAnnouncementManager(): AnnouncementManager
    {
        return $this->announcementManager;
    }

    /**
     * @return FactionManager
     */
    public function getFactionManager(): FactionManager
    {
        return $this->factionManager;
    }

    /**
     * @return ItemManager
     */
    public function getItemManager(): ItemManager
    {
        return $this->itemManager;
    }

    /**
     * @return NetworkManager
     */
    /* public function getNetworkManager(): NetworkManager {
         return $this->networkManager;
     }*/

    /**
     * @return KitManager
     */
    public function getKitManager(): KitManager
    {
        return $this->kitManager;
    }

    /**
     * @return CrateManager
     */
    public function getCrateManager(): CrateManager
    {
        return $this->crateManager;
    }

    /**
     * @return WatchdogManager
     */
    public function getWatchdogManager(): WatchdogManager
    {
        return $this->watchdogManager;
    }

    /**
     * @return KOTHManager
     */
    public function getKOTHManager(): KOTHManager
    {
        return $this->kothManager;
    }

    /**
     * @return WayPointManager
     */
    public function getWayPointManager(): WayPointManager
    {
        return $this->wayPointManager;
    }

    /**
     * @return YamlProvider
     */
    public function getYamlProvider(): YamlProvider
    {
        return $this->yamlProvider;
    }

    /**
     * @return CpsCounter
     */
    public function getCpsCounter(): CpsCounter
    {
        return $this->cpsCounter;
    }

    /**
     * @return CustomEnchantManager
     */
    public function getEnchantmentManager(): CustomEnchantManager
    {
        return $this->enchantmentManager;
    }
}