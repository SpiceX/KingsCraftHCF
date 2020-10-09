<?php /** @noinspection NullPointerExceptionInspection */

namespace hcf\item;

use Exception;
use hcf\faction\Claim;
use hcf\faction\Faction;
use hcf\HCF;
use hcf\HCFPlayer;
use hcf\item\entity\GrapplingHook;
use hcf\item\types\Crowbar;
use hcf\item\types\TeleportationBall;
use hcf\level\block\EndPortalFrame;
use hcf\level\block\MonsterSpawner;
use hcf\level\tile\MobSpawner;
use hcf\translation\Translation;
use hcf\translation\TranslationException;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\EnderPearl;
use pocketmine\item\Item;
use pocketmine\item\Pickaxe;
use pocketmine\level\particle\SmokeParticle;
use pocketmine\level\sound\AnvilBreakSound;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\utils\TextFormat;
use ReflectionException;

class ItemListener implements Listener
{

    /** @var HCF */
    private $core;

    /** @var array */
    private $ids = [
        Block::COAL_ORE,
        Block::DIAMOND_ORE,
        Block::EMERALD_ORE,
        Block::REDSTONE_ORE,
        Block::NETHER_QUARTZ_ORE
    ];

    /**
     * ItemListener constructor.
     *
     * @param HCF $core
     */
    public function __construct(HCF $core)
    {
        $this->core = $core;
    }

    /**
     * @priority HIGHEST
     * @param PlayerInteractEvent $event
     *
     * @throws TranslationException
     * @throws ReflectionException
     * @throws Exception
     */
    public function onPlayerInteract(PlayerInteractEvent $event): void
    {
        $item = $event->getItem();
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if (!$player instanceof HCFPlayer) {
            return;
        }
        if (($item instanceof EnderPearl || $item instanceof TeleportationBall) && $event->isCancelled() && 10 - (time() - $player->getEnderPearlTime()) <= 0) {
            $item->onClickAir($player, $player->getDirectionVector());
            if ($item instanceof EnderPearl) {
                if ($item->getDamage() === 1) {
                    $player->setEnderPearlTime(time() - 2);
                } else {
                    $player->setEnderPearlTime(time() - 1);
                }
            }
            $player->getInventory()->setItemInHand($item);
            return;
        }
        if ($item instanceof AntiTrapper && $player->hasAntiTrapperCooldown) {
            $event->setCancelled();
        }
        if ($item instanceof EdibleNetherStar && $player->hasStarCooldown) {
            $event->setCancelled();
        }
        if ($item instanceof Fireworks && $player->hasFireworksCooldown) {
            $event->setCancelled();
        }
        if ($item instanceof InvisibilitySak && $player->hasInvisibilitySakCooldown) {
            $event->setCancelled();
        }
        if ($item instanceof TeleportationBall && $player->hasTeleportationBallCooldown) {
            $event->setCancelled();
        }

        if ($event->isCancelled()) {
            return;
        }

        if ($item->getId() === Item::EXPERIENCE_BOTTLE) {
            $xp = 0;
            for ($i = 0; $i <= $item->getCount(); ++$i) {
                $xp += random_int(6, 18);
            }
            $player->addXp($xp);
            $player->getInventory()->removeItem($item);
            $event->setCancelled();
            return;
        }
        if ($player->isClaiming()) {
            if ($item->getId() !== Item::STICK) {
                return;
            }
            if ($player->getFaction() === null || $player->getFactionRole() !== Faction::LEADER) {
                $player->setClaiming(false);
                $player->setFirstClaimingPosition($player->getFirstClaimPosition());
                $player->setSecondClaimingPosition($player->getSecondClaimPosition());
                $player->getInventory()->remove(Item::get(Item::STICK));
                return;
            }
            if (abs($player->getX()) <= HCF::EDIT && abs($player->getZ()) <= HCF::EDIT) {
                $player->sendMessage(Translation::getMessage("claimOnceReachEdit"));
                return;
            }
            if ($event->getAction() === PlayerInteractEvent::LEFT_CLICK_BLOCK) {
                foreach ($this->core->getAreaManager()->getAreas() as $area) {
                    if ($area->isPositionInside($block)) {
                        $player->setClaiming(false);
                        $player->setFirstClaimingPosition($area->getFirstPosition());
                        $player->setSecondClaimingPosition($area->getSecondPosition());
                        $player->getInventory()->remove(Item::get(Item::STICK));
                        $player->sendMessage(Translation::getMessage("noPermission"));
                        return;
                    }
                }
                $player->setFirstClaimingPosition($block->asPosition());
                $player->sendMessage(Translation::getMessage("claimPositionSet", [
                    "place" => TextFormat::GREEN . "first"
                ]));
            } elseif ($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
                foreach ($this->core->getAreaManager()->getAreas() as $area) {
                    if ($area->isPositionInside($block)) {
                        $player->setClaiming(false);
                        $player->setFirstClaimingPosition($area->getFirstPosition());
                        $player->setSecondClaimingPosition($area->getSecondPosition());
                        $player->getInventory()->remove(Item::get(Item::STICK));
                        $player->sendMessage(Translation::getMessage("noPermission"));
                        return;
                    }
                }
                $player->setSecondClaimingPosition($block->asPosition());
                $player->sendMessage(Translation::getMessage("claimPositionSet", [
                    "place" => TextFormat::GREEN . "second"
                ]));
            }
            if ($player->getFirstClaimPosition() !== null && $player->getSecondClaimPosition() !== null) {
                $firstPosition = $player->getFirstClaimPosition();
                $firstX = $firstPosition->getX();
                $firstZ = $firstPosition->getZ();
                $secondPosition = $player->getSecondClaimPosition();
                $secondX = $secondPosition->getX();
                $secondZ = $secondPosition->getZ();
                $length = max($firstX, $secondX) - min($firstX, $secondX);
                $width = max($firstZ, $secondZ) - min($firstZ, $secondZ);
                if ($length <= 5 || $width <= 5) {
                    $player->sendMessage(Translation::getMessage("claimTooSmall"));
                    $player->setClaiming(false);
                    $player->setFirstClaimingPosition($firstPosition);
                    $player->setSecondClaimingPosition($secondPosition);
                    $player->getInventory()->remove(Item::get(Item::STICK));
                    return;
                }
                $amount = $length * $width;
                $price = $amount * 5;
                if ($player->isSneaking()) {
                    $claim = new Claim($player->getFaction(), $firstPosition, $secondPosition);
                    foreach ($claim->getChunkHashes() as $chunkHash) {
                        if ($this->core->getFactionManager()->getClaimByHash($chunkHash) === null) {
                            continue;
                        }
                    }
                    $player->setClaiming(false);
                    $player->setFirstClaimingPosition($firstPosition);
                    $player->setSecondClaimingPosition($secondPosition);
                    $player->getInventory()->remove(Item::get(Item::STICK));
                    $player->sendMessage(Translation::getMessage("overrideClaim"));
                    foreach ($this->core->getAreaManager()->getAreas() as $area) {
                        if ($claim->intersectsWith($area->getFirstPosition(), $area->getSecondPosition())) {
                            $player->setClaiming(false);
                            $player->setFirstClaimingPosition($area->getFirstPosition());
                            $player->setSecondClaimingPosition($area->getSecondPosition());
                            $player->getInventory()->remove(Item::get(Item::STICK));
                            $player->sendMessage(Translation::getMessage("noPermission"));
                            return;
                        }
                    }
                    if ($player->getFaction()->getBalance() < $price) {
                        $player->sendMessage(Translation::getMessage("factionNotEnoughMoney"));
                    } else {
                        $player->getFaction()->setNewClaim($claim);
                        $player->getFaction()->subtractMoney($price);
                        $player->sendMessage(Translation::getMessage("claimSuccess"));
                    }
                    $player->setClaiming(false);
                    $player->setFirstClaimingPosition($firstPosition);
                    $player->setSecondClaimingPosition($secondPosition);
                    $player->getInventory()->remove(Item::get(Item::STICK));
                    return;
                }

                $player->sendMessage(Translation::getMessage("claimConfirm", [
                    "amount" => TextFormat::GREEN . $amount,
                    "price" => TextFormat::YELLOW . $price,
                ]));
            }
        }
        $tag = $item->getNamedTagEntry(CustomItem::CUSTOM);
        if ($tag === null) {
            if ($block->getId() === Block::END_PORTAL_FRAME || $block->getId() === Block::MOB_SPAWNER) {
                $player->sendMessage(Translation::getMessage("requireCrowbar"));
            }
            return;
        }
        if ($tag instanceof CompoundTag) {
            if ((!$event->isCancelled()) && $item->getId() === Item::DIAMOND_HOE && $player->getLevel()->getFolderName() === "wild") {
                if ($block instanceof EndPortalFrame) {
                    $uses = $tag->getInt(Crowbar::END_PORTAL_FRAME_USES);
                    if ($uses > 0) {
                        $addItem = Item::get(Item::END_PORTAL_FRAME, 0, 1);
                        $spawnerUses = $tag->getInt(Crowbar::SPAWNER_USES);
                        if (!$player->getInventory()->canAddItem($addItem)) {
                            $player->sendMessage(Translation::getMessage("fullInventory"));
                            return;
                        }
                        $player->getLevel()->addSound(new AnvilBreakSound($player));
                        $player->getInventory()->addItem($addItem);
                        --$uses;
                        if ($uses === 0 && $spawnerUses === 0) {
                            $player->getInventory()->setItemInHand(Item::get(Item::AIR));
                        } else {
                            $tag->setInt(Crowbar::END_PORTAL_FRAME_USES, $uses);
                            $lore = [];
                            $lore[] = "";
                            $lore[] = TextFormat::RESET . TextFormat::AQUA . "Spawner Uses: " . TextFormat::WHITE . $spawnerUses;
                            $lore[] = TextFormat::RESET . TextFormat::AQUA . "End Portal Frame Uses: " . TextFormat::WHITE . $uses;
                            $lore[] = "";
                            $lore[] = TextFormat::RESET . TextFormat::GRAY . "Tap a spawner || an end portal frame to break && obtain it.";
                            $item->setLore($lore);
                            $player->getInventory()->setItemInHand($item);
                        }
                        $corners = $block->isValidPortal();
                        if (is_array($corners)) {
                            $corners = [
                                $corners[0]->getX(),
                                $corners[3]->getX(),
                                $corners[0]->getZ(),
                                $corners[3]->getZ(),
                                $corners[0]->getY()
                            ];
                            $block->destroyPortal($corners);
                        }
                        $block->getLevel()->addParticle(new SmokeParticle($block));
                        $block->getLevel()->setBlock($block, Block::get(Block::AIR));
                        return;
                    }
                    $player->sendMessage(Translation::getMessage("noMoreCrowbarUses"));
                    return;
                }
                if ($block instanceof MonsterSpawner) {
                    $uses = $tag->getInt(Crowbar::SPAWNER_USES);
                    if ($uses > 0) {
                        /** @var MobSpawner $tile */
                        $tile = $block->getLevel()->getTile($block);
                        if ($tile === null) {
                            return;
                        }
                        $addItem = Item::get(Item::MOB_SPAWNER, 0, 1, new CompoundTag("", [
                            new IntTag("EntityId", $tile->entityId)
                        ]));
                        $addItem->setCustomName(TextFormat::RESET . TextFormat::GOLD . $tile->getEntityType() . " Spawner");
                        $endPortalFrameUses = $tag->getInt(Crowbar::END_PORTAL_FRAME_USES);
                        if (!$player->getInventory()->canAddItem($addItem)) {
                            $player->sendMessage(Translation::getMessage("fullInventory"));
                            return;
                        }
                        $player->getLevel()->addSound(new AnvilBreakSound($player));
                        $player->getInventory()->addItem($addItem);
                        --$uses;
                        if ($uses === 0 && $endPortalFrameUses === 0) {
                            $player->getInventory()->setItemInHand(Item::get(Item::AIR));
                        } else {
                            $tag->setInt(Crowbar::SPAWNER_USES, $uses);
                            $lore = [];
                            $lore[] = "";
                            $lore[] = TextFormat::RESET . TextFormat::AQUA . "Spawner Uses: " . TextFormat::WHITE . $uses;
                            $lore[] = TextFormat::RESET . TextFormat::AQUA . "End Portal Frame Uses: " . TextFormat::WHITE . $endPortalFrameUses;
                            $lore[] = "";
                            $lore[] = TextFormat::RESET . TextFormat::GRAY . "Tap a spawner || an end portal frame to break && obtain it.";
                            $item->setLore($lore);
                            $player->getInventory()->setItemInHand($item);
                        }
                        $block->getLevel()->setBlock($block, Block::get(Block::AIR));
                        $block->getLevel()->removeTile($tile);
                        $block->getLevel()->addParticle(new SmokeParticle($block));
                        return;
                    }
                    $player->sendMessage(Translation::getMessage("noMoreCrowbarUses"));
                    return;
                }
            }
            if ($item->getId() === Item::FISHING_ROD) {
                if ($player->getGrapplingHook() === null) {
                    $nbt = Entity::createBaseNBT($player);
                    $hook = new GrapplingHook($player->getLevel(), $nbt, $player);
                    $hook->spawnToAll();
                    $uses = $tag->getInt(types\GrapplingHook::USES);
                    $uses--;
                    if ($uses === 0) {
                        $player->getInventory()->setItemInHand(Item::get(Item::AIR));
                    } else {
                        $tag->setInt(types\GrapplingHook::USES, $uses);
                        $lore = [];
                        $lore[] = "";
                        $lore[] = TextFormat::RESET . TextFormat::GREEN . "Uses left: $uses";
                        $lore[] = "";
                        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Travel to a place faster.";
                        $item->setLore($lore);
                        $player->getInventory()->setItemInHand($item);
                    }
                } elseif ($player->getGrapplingHook()->isOnGround()) {
                    $hook = $player->getGrapplingHook();
                    $hook->handleHookRetraction();
                    $uses = $tag->getInt(types\GrapplingHook::USES);
                    $uses--;
                    if ($uses === 0) {
                        $player->getInventory()->setItemInHand(Item::get(Item::AIR));
                    } else {
                        $tag->setInt(types\GrapplingHook::USES, $uses);
                        $lore = [];
                        $lore[] = "";
                        $lore[] = TextFormat::RESET . TextFormat::GREEN . "Uses left: $uses";
                        $lore[] = "";
                        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Travel to a place faster.";
                        $item->setLore($lore);
                        $player->getInventory()->setItemInHand($item);
                    }
                }
                $player->broadcastEntityEvent(AnimatePacket::ACTION_SWING_ARM);
            }
        }
    }


    /**
     * @priority HIGHEST
     * @param BlockBreakEvent $event
     * @throws Exception
     */
    public function onBlockBreak(BlockBreakEvent $event): void
    {
        if ($event->isCancelled()) {
            return;
        }
        $item = $event->getItem();
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if (!$player instanceof HCFPlayer) {
            return;
        }
        $blockId = $block->getId();
        if (($level = $item->getEnchantmentLevel(Enchantment::FORTUNE)) > 0) {
            if (!in_array($blockId, $this->ids, true)) {
                return;
            }
            $id = 0;
            switch ($blockId) {
                case Block::COAL_ORE:
                    $id = Item::COAL;
                    break;
                case Block::DIAMOND_ORE:
                    $id = Item::DIAMOND;
                    break;
                case Block::EMERALD_ORE:
                    $id = Item::EMERALD;
                    break;
                case Block::REDSTONE_ORE:
                    $id = Item::REDSTONE;
                    break;
                case Block::NETHER_QUARTZ_ORE:
                    $id = Item::NETHER_QUARTZ;
                    break;
            }
            $drops = [Item::get($id, 0, 1 + random_int(0, $level + 2))];
            $event->setDrops($drops);
        }
        if ($item instanceof Pickaxe) {
            switch ($blockId) {
                case Block::COAL_ORE:
                    $tag = "Coal";
                    break;
                case Block::GLOWING_REDSTONE_ORE:
                case Block::REDSTONE_ORE:
                    $tag = "Redstone";
                    break;
                case Block::LAPIS_ORE:
                    $tag = "Lapis";
                    break;
                case Block::IRON_ORE:
                    $tag = "Iron";
                    break;
                case Block::GOLD_ORE:
                    $tag = "Gold";
                    break;
                case Block::DIAMOND_ORE:
                    $tag = "Diamond";
                    break;
                case Block::EMERALD_ORE:
                    $tag = "Emerald";
                    break;
                default:
                    return;
            }
            if ($item->getNamedTagEntry(CustomItem::CUSTOM) === null) {
                $item->setNamedTagEntry(new CompoundTag(CustomItem::CUSTOM));
            }
            /** @var CompoundTag $compoundTag */
            $compoundTag = $item->getNamedTagEntry(CustomItem::CUSTOM);
            if ($compoundTag->hasTag($tag, IntTag::class)) {
                $amount = $compoundTag->getInt($tag);
                ++$amount;
                $compoundTag->setInt($tag, $amount);
            } else {
                if (!$compoundTag->hasTag("Coal", IntTag::class)) {
                    $compoundTag->setInt("Coal", 0);
                }
                if (!$compoundTag->hasTag("Redstone", IntTag::class)) {
                    $compoundTag->setInt("Redstone", 0);
                }
                if (!$compoundTag->hasTag("Lapis", IntTag::class)) {
                    $compoundTag->setInt("Lapis", 0);
                }
                if (!$compoundTag->hasTag("Iron", IntTag::class)) {
                    $compoundTag->setInt("Iron", 0);
                }
                if (!$compoundTag->hasTag("Gold", IntTag::class)) {
                    $compoundTag->setInt("Gold", 0);
                }
                if (!$compoundTag->hasTag("Diamond", IntTag::class)) {
                    $compoundTag->setInt("Diamond", 0);
                }
                if (!$compoundTag->hasTag("Emerald", IntTag::class)) {
                    $compoundTag->setInt("Emerald", 0);
                }
                $compoundTag->setInt($tag, 1);
            }
            $lore = [
                TextFormat::RESET . TextFormat::DARK_GRAY . "Coal: " . TextFormat::WHITE . $compoundTag->getInt("Coal"),
                TextFormat::RESET . TextFormat::RED . "Redstone: " . TextFormat::WHITE . $compoundTag->getInt("Redstone"),
                TextFormat::RESET . TextFormat::BLUE . "Lapis: " . TextFormat::WHITE . $compoundTag->getInt("Lapis"),
                TextFormat::RESET . TextFormat::GRAY . "Iron: " . TextFormat::WHITE . $compoundTag->getInt("Iron"),
                TextFormat::RESET . TextFormat::GOLD . "Gold: " . TextFormat::WHITE . $compoundTag->getInt("Gold"),
                TextFormat::RESET . TextFormat::AQUA . "Diamond: " . TextFormat::WHITE . $compoundTag->getInt("Diamond"),
                TextFormat::RESET . TextFormat::GREEN . "Emerald: " . TextFormat::WHITE . $compoundTag->getInt("Emerald")
            ];
            $event->getPlayer()->getInventory()->setItemInHand($item->setLore($lore));
        }
    }

    /**
     * @priority HIGHEST
     * @param EntityDamageEvent $event
     * @throws Exception
     */
    public function onEntityDamage(EntityDamageEvent $event): void
    {
        if ($event->isCancelled()) {
            return;
        }
        if ($event instanceof EntityDamageByEntityEvent) {
            $damager = $event->getDamager();
            if (!$damager instanceof HCFPlayer) {
                return;
            }
            $item = $damager->getInventory()->getItemInHand();
            if (($level = $item->getEnchantmentLevel(Enchantment::LOOTING)) <= 0) {
                return;
            }
            /** @var Living $entity */
            $entity = $event->getEntity();
            if ($entity instanceof HCFPlayer) {
                return;
            }
            if ($event->getFinalDamage() >= $entity->getHealth()) {
                foreach ($entity->getDrops() as $drop) {
                    $drop->setCount($drop->getCount() + random_int(1, $level));
                    $entity->getLevel()->dropItem($entity, $drop);
                }
            }
        }
    }
}