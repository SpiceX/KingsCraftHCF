<?php

namespace hcf\crate;

use hcf\crate\types\CommonCrate;
use hcf\crate\types\LegendaryCrate;
use hcf\crate\types\PortatilCrate;
use hcf\crate\types\RewardCrate;
use hcf\crate\types\SpecialCrate;
use hcf\crate\types\UncommonCrate;
use hcf\crate\types\UnknownCrate;
use hcf\HCF;
use pocketmine\level\Position;
use pocketmine\Player;

class CrateManager
{

    /** @var HCF */
    private $core;

    /** @var Crate[] */
    private $crates = [];

    /** @var PortatilCrate[] */
    private $portatilCrates = [];

    /**
     * CrateManager constructor.
     *
     * @param HCF $core
     */
    public function __construct(HCF $core)
    {
        $this->core = $core;
        $core->getServer()->getPluginManager()->registerEvents(new CrateListener($core), $core);
        $this->init();
        $this->loadCrates();
    }

    public function init(): void
    {
        $this->addCrate(new RewardCrate(new Position(-41, 69, 31, $this->core->getServer()->getDefaultLevel())));
        $this->addCrate(new CommonCrate(new Position(-35, 69, 36, $this->core->getServer()->getDefaultLevel())));
        $this->addCrate(new UncommonCrate(new Position(-38, 69, 34, $this->core->getServer()->getDefaultLevel())));
        $this->addCrate(new LegendaryCrate(new Position(-31, 69, 38, $this->core->getServer()->getDefaultLevel())));
        $this->addCrate(new UnknownCrate(new Position(-27, 69, 39, $this->core->getServer()->getDefaultLevel())));
        $this->addCrate(new SpecialCrate(new Position(-25, 69, 40, $this->core->getServer()->getDefaultLevel())));
    }

    /**
     * @param array $items
     * @return Reward[]
     */
    public function itemsToRewards(array $items): array
    {
        $rewards = [];
        foreach ($items as $item) {
            $rewards[] = new Reward($item, function (Player $player) use ($item) {
                if ($player->getInventory()->canAddItem($item)) {
                    $player->getInventory()->addItem($item);
                } else {
                    $player->getLevel()->dropItem($player->asVector3(), $item);
                }
            });
        }
        return $rewards;
    }

    public function loadCrates(): void
    {
        foreach (glob(HCF::getInstance()->getDataFolder() . "crates" . DIRECTORY_SEPARATOR . "*.cct") as $crateFile) {
            $file = fopen($crateFile, 'rb');
            $items = HCF::decodeInventory(fread($file, filesize($crateFile)));
            $this->crateParser(basename($crateFile, ".cct"), $items);
            fclose($file);
        }
        foreach (glob(HCF::getInstance()->getDataFolder() . "crates" . DIRECTORY_SEPARATOR . "*.pct") as $crateFile) {
            $file = fopen($crateFile, 'rb');
            $items = HCF::decodeInventory(fread($file, filesize($crateFile)));
            $portatilCrate = new PortatilCrate(basename($crateFile, '.pct'));
            $portatilCrate->rewardsFromItemList($items);
            $this->addPortatilCrate($portatilCrate);
            fclose($file);
        }
    }

    public function saveCrates(): void
    {
        foreach ($this->crates as $crate) {
            $crate->save();
        }
    }


    /**
     * @param string $crateName
     * @param array $items
     * @noinspection NullPointerExceptionInspection
     */
    private function crateParser(string $crateName, array $items): void
    {
        switch ($crateName) {
            case Crate::COMMON:
                $crate = $this->getCrate(Crate::COMMON);
                $crate->setRewards($this->itemsToRewards($items));
                break;
            case Crate::LEGENDARY:
                $crate = $this->getCrate(Crate::LEGENDARY);
                $crate->setRewards($this->itemsToRewards($items));
                break;
            case Crate::REWARD:
                $crate = $this->getCrate(Crate::REWARD);
                $crate->setRewards($this->itemsToRewards($items));
                break;
            case Crate::SPECIAL:
                $crate = $this->getCrate(Crate::SPECIAL);
                $crate->setRewards($this->itemsToRewards($items));
                break;
            case Crate::UNCOMMON:
                $crate = $this->getCrate(Crate::UNCOMMON);
                $crate->setRewards($this->itemsToRewards($items));
                break;
            case Crate::UNKNOWN:
                $crate = $this->getCrate(Crate::UNKNOWN);
                $crate->setRewards($this->itemsToRewards($items));
                break;
        }
    }

    /**
     * @return Crate[]
     */
    public function getCrates(): array
    {
        return $this->crates;
    }

    /**
     * @param string $identifier
     *
     * @return Crate|null
     */
    public function getCrate(string $identifier): ?Crate
    {
        return $this->crates[$identifier] ?? null;
    }

    /**
     * @param string $identifier
     *
     * @return PortatilCrate|null
     */
    public function getPortatilCrate(string $identifier): ?PortatilCrate
    {
        return $this->portatilCrates[$identifier] ?? null;
    }

    /**
     * @param Crate $crate
     */
    public function addCrate(Crate $crate): void
    {
        $this->crates[$crate->getCustomName()] = $crate;
    }

    /**
     * @param PortatilCrate $crate
     */
    public function addPortatilCrate(PortatilCrate $crate): void
    {
        $this->portatilCrates[$crate->getCustomName()] = $crate;
    }

    /**
     * @param Player $player
     * @param string $name
     * @param bool $save
     * @return PortatilCrate
     */
    public function createPortatilCrate(Player $player, string $name, bool $save = false): PortatilCrate
    {
        $portableCrate = new PortatilCrate($name);
        $portableCrate->exchangeInventory($player);

        if ($save) {
            $portableCrate->saveRewardsToNBT();
        }

        $this->addPortatilCrate($portableCrate);
        return $portableCrate;
    }

    /**
     * @return PortatilCrate[]
     */
    public function getPortatilCrates(): array
    {
        return $this->portatilCrates;
    }
}