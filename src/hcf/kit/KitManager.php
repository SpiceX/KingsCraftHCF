<?php

namespace hcf\kit;

use hcf\HCF;
use hcf\kit\types\ArcherKit;
use hcf\kit\types\AstroKit;
use hcf\kit\types\BardKit;
use hcf\kit\types\BuilderKit;
use hcf\kit\types\CustomKit;
use hcf\kit\types\DiamondKit;
use hcf\kit\types\FoodKit;
use hcf\kit\types\KingLegendKit;
use hcf\kit\types\LegendKit;
use hcf\kit\types\MinerKit;
use hcf\kit\types\RevenantKit;
use hcf\kit\types\RogueKit;
use hcf\kit\types\StarterKit;
use pocketmine\Player;

class KitManager
{

    /** @var HCF */
    private $core;

    /** @var Kit[] */
    private $kits;

    /**
     * KitManager constructor.
     *
     * @param HCF $core
     */
    public function __construct(HCF $core)
    {
        $this->core = $core;
        $core->getServer()->getPluginManager()->registerEvents(new KitListener($core), $core);
        $this->init();
    }

    public function init(): void
    {
        $this->registerKit(new FoodKit());
        $this->registerKit(new StarterKit());
        $this->registerKit(new LegendKit());
        $this->registerKit(new KingLegendKit());
        $this->registerKit(new RevenantKit());
        $this->registerKit(new AstroKit());
        $this->registerKit(new ArcherKit());
        $this->registerKit(new BardKit());
        $this->registerKit(new BuilderKit());
        $this->registerKit(new MinerKit());
        $this->registerKit(new RogueKit());
        $this->registerKit(new DiamondKit());
    }

    /**
     * @param string $kitName
     * @param Player $player
     */
    public function createFromInventory(string $kitName, Player $player): void
    {
        $kit = new CustomKit($kitName);
        $kit->addFromInventory($player->getInventory());
        foreach ($player->getArmorInventory()->getContents() as $content) {
            $kit->addItem($content);
        }
        $this->registerKit($kit);
    }

    public function saveCustomKits(): void
    {
        foreach ($this->kits as $kit) {
            if ($kit instanceof CustomKit) {
                $kit->save();
            }
        }
    }

    public function loadCustomKits(): void
    {
        foreach (glob($this->getCore()->getDataFolder() . "kits" . DIRECTORY_SEPARATOR . "*.ekt") as $kitFile) {
            $file = fopen($kitFile, 'rb');
            $items = HCF::decodeInventory(fread($file, filesize($kitFile)));
            $kit = new CustomKit(basename($kitFile, ".ekt"));
            foreach ($items as $item) {
                $item->setCustomName("§d§l{$kit->getName()} {$item->getName()}");
                $kit->addItem($item);
            }
            $this->registerKit($kit);
            fclose($file);
        }
    }

    /**
     * @param Kit $kit
     */
    public function registerKit(Kit $kit): void
    {
        $this->kits[$kit->getName()] = $kit;
    }

    /**
     * @param string $name
     *
     * @return Kit|null
     */
    public function getKitByName(string $name): ?Kit
    {
        return $this->kits[$name] ?? null;
    }

    public function removeKitByName(string $name): void
    {
        if (isset($this->kits[$name])) {
            unset($this->kits[$name]);
        }
        @unlink($this->getCore()->getDataFolder() . 'kits' . DIRECTORY_SEPARATOR . "$name.ekt");
    }

    /**
     * @return Kit[]
     */
    public function getKits(): array
    {
        return $this->kits;
    }

    /**
     * @return HCF
     */
    public function getCore(): HCF
    {
        return $this->core;
    }
}