<?php

namespace hcf\shop;

use hcf\HCF;
use hcf\HCFPlayer;
use hcf\translation\Translation;
use hcf\translation\TranslationException;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\tile\Sign;
use pocketmine\utils\TextFormat;

class ShopListener implements Listener
{

    /** @var HCF */
    private $core;

    /**
     * ShopListener constructor.
     *
     * @param HCF $core
     */
    public function __construct(HCF $core)
    {
        $this->core = $core;
    }

    /**
     * @priority NORMAL
     * @param SignChangeEvent $event
     *
     * @throws ShopException
     */
    public function onSignChange(SignChangeEvent $event): void
    {
        $lines = $event->getLines();
        if ($lines[0] === "[sell]" or $lines[0] === "[buy]") {
            $player = $event->getPlayer();
            if (!$player->isOp()) {
                return;
            }
            if ($lines[0] === "[buy]") {
                $type = ShopSign::BUY;
            } elseif ($lines[0] === "[sell]") {
                $type = ShopSign::SELL;
            }
            if (!isset($type)) {
                throw new ShopException("Invalid shop type!");
            }
            $item = null;
            if (strpos($lines[1], ':')) {
                $itemData = explode(':', $lines[1]);
                $item = Item::get($itemData[0], $itemData[1]);
            } else {
                if (is_numeric($lines[1])) {
                    $item = Item::get((int)$lines[1]);
                }
            }
            if (is_numeric($lines[2]) and is_numeric($lines[3])) {
                if ($item instanceof Item) {
                    $amount = (int)$lines[3];
                    $item->setCount($amount);
                    if ($type === ShopSign::BUY) {
                        $line0 = TextFormat::GREEN . "[Buy]";
                    }
                    elseif ($type === ShopSign::SELL) {
                        $line0 = TextFormat::RED . "[Sell]";
                    } else {
                        $line0 = TextFormat::DARK_RED . "Unknown";
                    }
                    $name = $item->getName();
                    if (strlen($name) > 16) {
                        $name = substr($name, 0, 16);
                    }
                    $line1 = TextFormat::BLACK . $name;
                    if ($item->hasCustomName()) {
                        $line1 = $item->getCustomName();
                    }
                    $line2 = TextFormat::BLACK . "$$lines[2]";
                    $line3 = TextFormat::BLACK . $amount;
                    $event->setLines([$line0, $line1, $line2, $line3]);
                    $this->core->getShopManager()->addShopSign(new ShopSign($event->getBlock()->asPosition(), $item, $lines[2], $type));
                }
            }

        }
    }

    /**
     * @priority NORMAL
     * @param PlayerInteractEvent $event
     *
     * @throws TranslationException
     */
    public function onPlayerInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $tile = $player->getLevel()->getTile($block);
        if (!$player instanceof HCFPlayer) {
            return;
        }
        if ($tile instanceof Sign) {
            $shopSign = $this->core->getShopManager()->getShopSign($block->asPosition());
            if ($shopSign === null) {
                return;
            }
            if ($shopSign->getType() === ShopSign::BUY) {
                if ($shopSign->getPrice() > $player->getBalance()) {
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
            } else {
                if (!$player->getInventory()->contains($shopSign->getItem())) {
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
    public function onBlockBreak(BlockBreakEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $tile = $block->getLevel()->getTile($block->asPosition());
        if ($tile instanceof Sign) {
            $shopSign = $this->core->getShopManager()->getShopSign($block->asPosition());
            if ($shopSign === null) {
                return;
            }
            if (!$player->isOp()) {
                $event->setCancelled();
                $player->sendMessage(Translation::getMessage("noPermission"));
                return;
            }
            $this->core->getShopManager()->removeShopSign($shopSign);
        }
    }
}
