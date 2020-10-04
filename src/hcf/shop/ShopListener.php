<?php

namespace hcf\shop;

use hcf\HCF;
use hcf\HCFPlayer;
use hcf\translation\Translation;
use hcf\translation\TranslationException;
use pocketmine\block\Block;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\tile\Sign;
use pocketmine\utils\TextFormat;

class ShopListener implements Listener {

    /** @var HCF */
    private $core;

    /** @var Block[] */
    private $queue = [];

    /**
     * ShopListener constructor.
     *
     * @param HCF $core
     */
    public function __construct(HCF $core) {
        $this->core = $core;
    }

    /**
     * @priority NORMAL
     * @param SignChangeEvent $event
     *
     * @throws TranslationException
     */
    public function onSignChange(SignChangeEvent $event): void {
        $lines = $event->getLines();
        if($lines[0] === "[shop]") {
            $player = $event->getPlayer();
            if(!$player->isOp()) {
                return;
            }
            if($lines[1] === "sell" or $lines[1] === "buy") {
                if(is_numeric($lines[2]) and is_numeric($lines[3])) {
                    $this->queue[$player->getRawUniqueId()] = $event->getBlock();
                    $player->sendMessage(Translation::getMessage("selectItemMakeShop"));
                }
            }
        }
    }

    /**
     * @priority NORMAL
     * @param PlayerInteractEvent $event
     *
     * @throws ShopException
     * @throws TranslationException
     */
    public function onPlayerInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $item = $event->getItem();
        $tile = $player->getLevel()->getTile($block);
        if(!$player instanceof HCFPlayer) {
            return;
        }
        if(isset($this->queue[$player->getRawUniqueId()])) {
            if($block->equals($this->queue[$player->getRawUniqueId()])) {
                if($tile instanceof Sign) {
                    $lines = $tile->getText();
                    if($lines[1] === "buy") {
                        $type = ShopSign::BUY;
                    }
                    elseif($lines[1] === "sell") {
                        $type = ShopSign::SELL;
                    }
                    if(!isset($type)) {
                        throw new ShopException("Invalid shop type!");
                    }
                    $amount = (int)$lines[3];
                    $item->setCount($amount);
                    $line1 = TextFormat::GOLD . TextFormat::BOLD . ucfirst($lines[1]) . " Shop";
                    $name = $item->getName();
                    if(strlen($name) > 16) {
                        $name = substr($name, 0, 16);
                    }
                    $line2 = TextFormat::WHITE . $name;
                    if($item->hasCustomName()) {
                        $line2 = $item->getCustomName();
                    }
                    $line3 = TextFormat::AQUA . "Price: " . TextFormat::WHITE . "$$lines[2]";
                    $line4 = TextFormat::YELLOW . "Amount: " . TextFormat::WHITE . $amount;
                    $tile->setText($line1, $line2, $line3, $line4);
                    $this->core->getShopManager()->addShopSign(new ShopSign($block->asPosition(), $item, $lines[2], $type));
                }
            }
            unset($this->queue[$player->getRawUniqueId()]);
            return;
        }
        if($tile instanceof Sign) {
            $shopSign = $this->core->getShopManager()->getShopSign($block->asPosition());
            if($shopSign === null) {
                return;
            }
            if($shopSign->getType() === ShopSign::BUY) {
                if($shopSign->getPrice() > $player->getBalance()) {
                    $player->sendMessage(Translation::getMessage("notEnoughMoney"));
                    return;
                }
                $player->sendMessage(Translation::getMessage("buyShopConfirmed", [
                    "amount" => TextFormat::YELLOW . $shopSign->getItem()->getCount(),
                    "name" => $shopSign->getItem()->hasCustomName() ? $shopSign->getItem()->getCustomName() : TextFormat::AQUA . $shopSign->getItem()->getName(),
                    "price" => TextFormat::GOLD . "$" . $shopSign->getPrice()
                ]));
                $player->getInventory()->addItem($shopSign->getItem());
                $player->subtractFromBalance($shopSign->getPrice());
            }
            else {
                if(!$player->getInventory()->contains($shopSign->getItem())) {
                    $player->sendMessage(Translation::getMessage("sellItemNotFound"));
                    return;
                }
                $player->sendMessage(Translation::getMessage("sellShopConfirmed", [
                    "amount" => TextFormat::YELLOW . $shopSign->getItem()->getCount(),
                    "name" => $shopSign->getItem()->hasCustomName() ? $shopSign->getItem()->getCustomName() : TextFormat::AQUA . $shopSign->getItem()->getName(),
                    "price" => TextFormat::GOLD . "$" . $shopSign->getPrice()
                ]));
                $player->addToBalance($shopSign->getPrice());
                $player->getInventory()->removeItem($shopSign->getItem());
                return;
            }
            return;
        }
    }

    /**
     * @priority NORMAL
     * @param BlockBreakEvent $event
     *
     * @throws TranslationException
     */
    public function onBlockBreak(BlockBreakEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $tile = $block->getLevel()->getTile($block->asPosition());
        if($tile instanceof Sign) {
            $shopSign = $this->core->getShopManager()->getShopSign($block->asPosition());
            if($shopSign === null) {
                return;
            }
            if(!$player->isOp()) {
                $event->setCancelled();
                $player->sendMessage(Translation::getMessage("noPermission"));
                return;
            }
            $this->core->getShopManager()->removeShopSign($shopSign);
        }
    }
}
