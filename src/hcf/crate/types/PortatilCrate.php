<?php


namespace hcf\crate\types;


use hcf\crate\Crate;
use hcf\crate\Reward;
use hcf\HCF;
use hcf\HCFPlayer;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\Player;

class PortatilCrate extends Crate
{

    public const ITEMS_TAG = 'Items';

    /** @var string */
    private $customName;
    /** @var Item */
    private $chest;

    /**
     * PortatilCrate constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        parent::__construct(self::PORTATIL, new Position(), []);
        $this->customName = $name;
        $this->chest = Item::get(Item::CHEST);
    }

    /**
     * @param Player $player
     */
    public function exchangeInventory(Player $player)
    {
        $rewards = [];
        foreach ($player->getInventory()->getContents() as $item) {
            $rewards[] = new Reward($item, function (Player $player) use ($item) {
                if ($player->getInventory()->canAddItem($item)) {
                    $player->getInventory()->addItem($item);
                } else {
                    $player->getLevel()->dropItem($player->asVector3(), $item);
                }
            });
        }
        $this->setRewards($rewards);
    }

    /**
     * @param array $items
     */
    public function rewardsFromItemList(array $items)
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
        $this->setRewards($rewards);
        $this->chest->getNamedTag()->setString(self::ITEMS_TAG, HCF::encodeItemList($items));
    }

    public function saveRewardsToNBT(): void
    {
        if (empty($this->rewards)) {
            return;
        }
        $items = [];
        foreach ($this->rewards as $reward) {
            $items[] = $reward->getItem();
        }
        $encoded = HCF::encodeItemList($items);
        $cratePath = $this->getPlugin()->getDataFolder() . 'crates' . DIRECTORY_SEPARATOR . "{$this->getCustomName()}.pct";
        $crateFile = @fopen($cratePath, 'wb') or die("Unable to open crate file!");
        @fwrite($crateFile, $encoded);
        @fclose($crateFile);
    }

    /**
     * @param HCFPlayer $player
     */
    public function spawnTo(HCFPlayer $player): void
    {
        $player->getInventory()->clearAll();
        $this->chest->getNamedTag()->setString("Crate", "PortatilCrate");
        $this->chest->setCustomName($this->customName);
        $player->getInventory()->addItem($this->chest);
    }

    /**
     * @param HCFPlayer $player
     */
    public function despawnTo(HCFPlayer $player): void
    {
        foreach ($player->getInventory()->getContents() as $item) {
            if ($item->getCustomName() === $this->customName && $item->getNamedTag()->hasTag("Crate")) {
                $player->getInventory()->removeItem($item);
            }
        }
    }

    /**
     * @return string
     */
    public function getCustomName(): string
    {
        return $this->customName;
    }

    /**
     * @param string $customName
     */
    public function setCustomName(string $customName): void
    {
        $this->customName = $customName;
    }

    /**
     * @return Item
     */
    public function getChest(): Item
    {
        return $this->chest;
    }
}