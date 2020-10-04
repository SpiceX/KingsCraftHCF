<?php

namespace hcf\shop;

use hcf\HCF;
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
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("SELECT x, y, z, level, item, type, price FROM shops");
        $stmt->execute();
        $stmt->bind_result($x, $y, $z, $level, $item, $type, $price);;
        while($stmt->fetch()) {
            $this->shopSigns[] = new ShopSign(new Position($x, $y, $z, $this->core->getServer()->getLevelByName($level)), HCF::decodeItem($item), $price, $type);
        }
        $stmt->close();
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
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("INSERT INTO shops(x, y, z, level, item, type, price) VALUES(?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiissii", $x, $y, $z, $level, $item, $type, $price);
        $stmt->execute();
        $stmt->close();
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
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("DELETE FROM shops WHERE x = ? AND y = ? AND z = ? AND level = ? AND item = ? AND type = ? AND price = ?");
        $stmt->bind_param("iiissii", $x, $y, $z, $level, $item, $type, $price);
        $stmt->execute();
        $stmt->close();
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