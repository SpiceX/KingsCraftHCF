<?php

namespace hcf\shop;

use hcf\HCF;
use PDO;
use pocketmine\level\Position;

class ShopManager {

    /** @var HCF */
    private $core;

    /** @var ShopSign[] */
    private $shopSigns = [];

    /**
     * ShopManager constructor.
     *
     * @param HCF $core
     */
    public function __construct(HCF $core) {
        $this->core = $core;
        $this->init();
        $core->getServer()->getPluginManager()->registerEvents(new ShopListener($core), $core);
    }

    public function init(): void {
        $stmt = $this->core->getMySQLProvider()->getDatabase()->query("SELECT x, y, z, level, item, type, price FROM shops");
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->shopSigns[] = new ShopSign(new Position($row['x'], $row['y'], $row['z'], $this->core->getServer()->getLevelByName($row['level'])), HCF::decodeItem($row['item']), $row['price'], $row['type']);
        }
        $stmt->closeCursor();
    }

    /**
     * @param ShopSign $sign
     */
    public function addShopSign(ShopSign $sign): void {
        $this->shopSigns[] = $sign;
        $x = $sign->getPosition()->getFloorX();
        $y = $sign->getPosition()->getFloorY();
        $z = $sign->getPosition()->getFloorZ();
        $level = $sign->getPosition()->getLevel()->getName();
        $item = HCF::encodeItem($sign->getItem());
        $type = $sign->getType();
        $price = $sign->getPrice();
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("INSERT INTO shops(x, y, z, level, item, type, price) VALUES(:x, :y, :z, :level, :item, :type, :price)");
        $stmt->bindParam(':x', $x);
        $stmt->bindParam(':y', $y);
        $stmt->bindParam(':z', $z);
        $stmt->bindParam(':level', $level);
        $stmt->bindParam(':item', $item);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':price', $price);
        $stmt->execute();
        $stmt->closeCursor();
    }

    /**
     * @param ShopSign $sign
     */
    public function removeShopSign(ShopSign $sign): void {
        foreach($this->shopSigns as $key => $shopSign) {
            if($shopSign->getPosition()->equals($sign->getPosition())) {
                unset($this->shopSigns[$key]);
            }
        }
        $x = $sign->getPosition()->getFloorX();
        $y = $sign->getPosition()->getFloorY();
        $z = $sign->getPosition()->getFloorZ();
        $level = $sign->getPosition()->getLevel()->getName();
        $item = HCF::encodeItem($sign->getItem());
        $type = $sign->getType();
        $price = $sign->getPrice();
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("DELETE FROM shops WHERE x = :x AND y = :y AND z = :z AND level = :level AND item = :item AND type = :type AND price = :price");
        $stmt->bindParam(':x', $x);
        $stmt->bindParam(':y', $y);
        $stmt->bindParam(':z', $z);
        $stmt->bindParam(':level', $level);
        $stmt->bindParam(':item', $item);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':price', $price);
        $stmt->execute();
        $stmt->closeCursor();
    }

    /**
     * @param Position $position
     *
     * @return ShopSign|null
     */
    public function getShopSign(Position $position): ?ShopSign {
        foreach($this->shopSigns as $key => $shopSign) {
            if($shopSign->getPosition()->equals($position)) {
                return $this->shopSigns[$key];
            }
        }
        return null;
    }
}