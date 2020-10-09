<?php /** @noinspection ProperNullCoalescingOperatorUsageInspection */

/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */

namespace hcf\level;

use hcf\HCF;
use hcf\level\block\Anvil;
use hcf\level\block\BrewingStand;
use hcf\level\block\Carrots;
use hcf\level\block\Dirt;
use hcf\level\block\EnchantingTable;
use hcf\level\block\EndPortal;
use hcf\level\block\EndPortalFrame;
use hcf\level\block\Grass;
use hcf\level\block\MelonStem;
use hcf\level\block\MonsterSpawner;
use hcf\level\block\NetherWartPlant;
use hcf\level\block\Obsidian;
use hcf\level\block\Portal;
use hcf\level\generator\EmptyGenerator;
use hcf\level\generator\NormalGenerator;
use hcf\level\task\GlowstoneResetTask;
use hcf\level\tile\Beacon;
use hcf\level\tile\Hopper;
use hcf\level\tile\MobSpawner;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\Chest;
use pocketmine\block\Fence;
use pocketmine\block\NetherBrickFence;
use pocketmine\block\Tripwire;
use pocketmine\block\WoodenFence;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\Living;
use pocketmine\item\Item;
use pocketmine\item\Potion;
use pocketmine\level\generator\GeneratorManager;
use pocketmine\level\Position;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\tile\Tile;
use pocketmine\utils\TextFormat;
use ReflectionClass;
use ReflectionException;

class LevelManager
{

    public const STACK_TAG = "Stack";
    public const STACK_SIZE = "{SIZE}";
    public const STACK_NAME = "{NAME}";

    /** @var HCF */
    private $core;

    /** @var BrewingRecipe[] */
    private $brewingRecipes = [];

    /** @var GlowstoneMountain */
    private $glowstoneMountain;

    /** @var string */
    private static $nametag;

    /**
     * LevelManager constructor.
     *
     * @param HCF $core
     *
     * @throws ReflectionException
     */
    public function __construct(HCF $core)
    {
        $this->core = $core;
        $core->getServer()->getPluginManager()->registerEvents(new LevelListener($core), $core);
        self::$nametag = TextFormat::RESET . TextFormat::BOLD . TextFormat::GOLD . self::STACK_NAME . TextFormat::RESET . TextFormat::DARK_GRAY . " [" . TextFormat::YELLOW . "x" . self::STACK_SIZE . TextFormat::DARK_GRAY . "]";
        $this->init();
        $this->glowstoneMountain = new GlowstoneMountain(new Position(-343, 36, 345, $this->core->getServer()->getLevelByName("nether")), new Position(-355, 40, 335, $this->core->getServer()->getLevelByName("nether")));
        $core->getScheduler()->scheduleRepeatingTask(new GlowstoneResetTask($this), 18000);
    }

    /**
     * @throws ReflectionException
     */
    public function init(): void
    {
        Tile::registerTile(Hopper::class);
        Tile::registerTile(\hcf\level\tile\BrewingStand::class);
        Tile::registerTile(MobSpawner::class);
        Tile::registerTile(Beacon::class);
        BlockFactory::registerBlock(new \hcf\level\block\Hopper(), true);
        BlockFactory::registerBlock(new BrewingStand(), true);
        BlockFactory::registerBlock(new EndPortal(), true);
        BlockFactory::registerBlock(new EndPortalFrame(), true);
        BlockFactory::registerBlock(new Portal(), true);
        BlockFactory::registerBlock(new Obsidian(), true);
        BlockFactory::registerBlock(new MonsterSpawner(), true);
        BlockFactory::registerBlock(new Anvil(), true);
        BlockFactory::registerBlock(new MelonStem(), true);
        BlockFactory::registerBlock(new NetherWartPlant(), true);
        BlockFactory::registerBlock(new Carrots(), true);
        BlockFactory::registerBlock(new Dirt(), true);
        BlockFactory::registerBlock(new Grass(), true);
        BlockFactory::registerBlock(new EnchantingTable(), true);
        BlockFactory::registerBlock(new \hcf\level\block\Beacon(), true);
        BlockFactory::registerBlock(new class() extends Chest {

            /**
             * @return AxisAlignedBB|null
             */
            protected function recalculateBoundingBox(): ?AxisAlignedBB
            {
                return new AxisAlignedBB(
                    $this->x - 0.1,
                    $this->y,
                    $this->z - 0.1,
                    $this->x + 0.8,
                    $this->y + 0.95,
                    $this->z + 0.8
                );
            }
        }, true);
        BlockFactory::registerBlock(new class() extends WoodenFence {

            /**
             * @return float
             */
            public function getThickness(): float
            {
                return 0.125;
            }

            /**
             * @return AxisAlignedBB|null
             */
            protected function recalculateBoundingBox(): ?AxisAlignedBB
            {
                $width = 0.5 - $this->getThickness() / 2;
                return new AxisAlignedBB(
                    $this->x + ($this->canConnect($this->getSide(Vector3::SIDE_WEST)) ? 0 : $width),
                    $this->y,
                    $this->z + ($this->canConnect($this->getSide(Vector3::SIDE_NORTH)) ? 0 : $width),
                    $this->x + 1 - ($this->canConnect($this->getSide(Vector3::SIDE_EAST)) ? 0 : $width),
                    $this->y + 1,
                    $this->z + 1 - ($this->canConnect($this->getSide(Vector3::SIDE_SOUTH)) ? 0 : $width)
                );
            }

            /**
             * @return array
             */
            protected function recalculateCollisionBoxes(): array
            {
                $inset = 0.5 - $this->getThickness() / 2;
                /** @var AxisAlignedBB[] $bbs */
                $bbs = [];
                $connectWest = $this->canConnect($this->getSide(Vector3::SIDE_WEST));
                $connectEast = $this->canConnect($this->getSide(Vector3::SIDE_EAST));
                if ($connectWest || $connectEast) {
                    //X axis (west/east)
                    $bbs[] = new AxisAlignedBB(
                        $this->x + ($connectWest ? 0 : $inset),
                        $this->y,
                        $this->z + $inset,
                        $this->x + 1 - ($connectEast ? 0 : $inset),
                        $this->y + 1,
                        $this->z + 1 - $inset
                    );
                }
                $connectNorth = $this->canConnect($this->getSide(Vector3::SIDE_NORTH));
                $connectSouth = $this->canConnect($this->getSide(Vector3::SIDE_SOUTH));
                if ($connectNorth || $connectSouth) {
                    //Z axis (north/south)
                    $bbs[] = new AxisAlignedBB(
                        $this->x + $inset,
                        $this->y,
                        $this->z + ($connectNorth ? 0 : $inset),
                        $this->x + 1 - $inset,
                        $this->y + 1,
                        $this->z + 1 - ($connectSouth ? 0 : $inset)
                    );
                }
                if (empty($bbs)) {
                    return [
                        new AxisAlignedBB(
                            $this->x + $inset,
                            $this->y,
                            $this->z + $inset,
                            $this->x + 1 - $inset,
                            $this->y + 1,
                            $this->z + 1 - $inset
                        )
                    ];
                }
                return $bbs;
            }

            /**
             * @param Block $block
             *
             * @return bool
             */
            public function canConnect(Block $block): bool
            {
                if ($block instanceof Fence) {
                    return true;
                }
                $bb = $block->getBoundingBox();
                return $bb !== null and $bb->getAverageEdgeLength() >= 1;
            }
        }, true);
        BlockFactory::registerBlock(new class() extends NetherBrickFence {

            /**
             * @return float
             */
            public function getThickness(): float
            {
                return 0.125;
            }

            /**
             * @return AxisAlignedBB|null
             */
            protected function recalculateBoundingBox(): ?AxisAlignedBB
            {
                $width = 0.5 - $this->getThickness() / 2;
                return new AxisAlignedBB(
                    $this->x + ($this->canConnect($this->getSide(Vector3::SIDE_WEST)) ? 0 : $width),
                    $this->y,
                    $this->z + ($this->canConnect($this->getSide(Vector3::SIDE_NORTH)) ? 0 : $width),
                    $this->x + 1 - ($this->canConnect($this->getSide(Vector3::SIDE_EAST)) ? 0 : $width),
                    $this->y + 1,
                    $this->z + 1 - ($this->canConnect($this->getSide(Vector3::SIDE_SOUTH)) ? 0 : $width)
                );
            }

            /**
             * @return array
             */
            protected function recalculateCollisionBoxes(): array
            {
                $inset = 0.5 - $this->getThickness() / 2;
                /** @var AxisAlignedBB[] $bbs */
                $bbs = [];
                $connectWest = $this->canConnect($this->getSide(Vector3::SIDE_WEST));
                $connectEast = $this->canConnect($this->getSide(Vector3::SIDE_EAST));
                if ($connectWest || $connectEast) {
                    //X axis (west/east)
                    $bbs[] = new AxisAlignedBB(
                        $this->x + ($connectWest ? 0 : $inset),
                        $this->y,
                        $this->z + $inset,
                        $this->x + 1 - ($connectEast ? 0 : $inset),
                        $this->y + 1,
                        $this->z + 1 - $inset
                    );
                }
                $connectNorth = $this->canConnect($this->getSide(Vector3::SIDE_NORTH));
                $connectSouth = $this->canConnect($this->getSide(Vector3::SIDE_SOUTH));
                if ($connectNorth || $connectSouth) {
                    //Z axis (north/south)
                    $bbs[] = new AxisAlignedBB(
                        $this->x + $inset,
                        $this->y,
                        $this->z + ($connectNorth ? 0 : $inset),
                        $this->x + 1 - $inset,
                        $this->y + 1,
                        $this->z + 1 - ($connectSouth ? 0 : $inset)
                    );
                }
                if (empty($bbs)) {
                    return [
                        new AxisAlignedBB(
                            $this->x + $inset,
                            $this->y,
                            $this->z + $inset,
                            $this->x + 1 - $inset,
                            $this->y + 1,
                            $this->z + 1 - $inset
                        )
                    ];
                }
                return $bbs;
            }

            /**
             * @param Block $block
             *
             * @return bool
             */
            public function canConnect(Block $block): bool
            {
                if ($block instanceof Fence) {
                    return true;
                }
                $bb = $block->getBoundingBox();
                return $bb !== null and $bb->getAverageEdgeLength() >= 1;
            }
        }, true);
        BlockFactory::registerBlock(new class() extends Tripwire {

            /**
             * @return AxisAlignedBB|null
             */
            protected function recalculateBoundingBox(): ?AxisAlignedBB
            {
                return new AxisAlignedBB(
                    $this->x - 0.99,
                    $this->y - 0.99,
                    $this->z - 0.99,
                    $this->x - 0.98,
                    $this->y - 0.98,
                    $this->z - 0.98
                );
            }
        }, true);
        $server = $this->core->getServer();
        GeneratorManager::addGenerator(NormalGenerator::class, "normal", true);
        GeneratorManager::addGenerator(EmptyGenerator::class, "nether", true);
        GeneratorManager::addGenerator(EmptyGenerator::class, "endy");
        if (!$server->loadLevel("wild")) {
            $server->generateLevel("wild", time(), GeneratorManager::getGenerator("normal"));
        }
        $world = $server->getDefaultLevel();
        $server->setDefaultLevel($server->getLevelByName("wild"));
        $server->unloadLevel($world);
        if (!$server->loadLevel("nether")) {
            $server->generateLevel("nether", time(), GeneratorManager::getGenerator("nether"));
        }
        if (!$server->loadLevel("ender")) {
            $server->generateLevel("ender", time(), GeneratorManager::getGenerator("endy"));
        }
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::AWKWARD, 1), Item::get(Item::NETHER_WART, 0, 1), Item::get(Item::POTION, Potion::WATER, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::THICK, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::WATER, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LONG_MUNDANE, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::WATER, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::MUNDANE, 1), Item::get(Item::GHAST_TEAR, 0, 1), Item::get(Item::POTION, Potion::WATER, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::MUNDANE, 1), Item::get(Item::GLISTERING_MELON, 0, 1), Item::get(Item::POTION, Potion::WATER, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::MUNDANE, 1), Item::get(Item::BLAZE_POWDER, 0, 1), Item::get(Item::POTION, Potion::WATER, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::MUNDANE, 1), Item::get(Item::MAGMA_CREAM, 0, 1), Item::get(Item::POTION, Potion::WATER, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::MUNDANE, 1), Item::get(Item::SUGAR, 0, 1), Item::get(Item::POTION, Potion::WATER, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::MUNDANE, 1), Item::get(Item::SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::WATER, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::MUNDANE, 1), Item::get(Item::RABBIT_FOOT, 0, 1), Item::get(Item::POTION, Potion::WATER, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::REGENERATION, 1), Item::get(Item::GHAST_TEAR, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LONG_REGENERATION, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::REGENERATION, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::STRONG_REGENERATION, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::REGENERATION, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::POISON, 1), Item::get(Item::SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LONG_POISON, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::POISON, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::STRONG_POISON, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::POISON, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::HEALING, 1), Item::get(Item::GLISTERING_MELON, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::STRONG_HEALING, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::HEALING, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::WATER_BREATHING, 1), Item::get(Item::PUFFERFISH, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LONG_WATER_BREATHING, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::WATER_BREATHING, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::SWIFTNESS, 1), Item::get(Item::SUGAR, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LONG_SWIFTNESS, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::SWIFTNESS, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::STRONG_SWIFTNESS, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::SWIFTNESS, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::FIRE_RESISTANCE, 1), Item::get(Item::MAGMA_CREAM, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LONG_FIRE_RESISTANCE, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::FIRE_RESISTANCE, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LEAPING, 1), Item::get(Item::RABBIT_FOOT, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LONG_LEAPING, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::LEAPING, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::STRONG_LEAPING, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::LEAPING, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::SLOWNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::FIRE_RESISTANCE, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::SLOWNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::SWIFTNESS, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::SLOWNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::LEAPING, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LONG_SLOWNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::LONG_FIRE_RESISTANCE, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LONG_SLOWNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::LONG_LEAPING, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LONG_SLOWNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::LONG_SWIFTNESS, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LONG_SLOWNESS, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::SLOWNESS, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::NIGHT_VISION, 1), Item::get(Item::GOLDEN_CARROT, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LONG_NIGHT_VISION, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::NIGHT_VISION, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::INVISIBILITY, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::NIGHT_VISION, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LONG_INVISIBILITY, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::INVISIBILITY, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LONG_INVISIBILITY, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::LONG_NIGHT_VISION, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::AWKWARD, 1), Item::get(Item::NETHER_WART, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::THICK, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::LONG_MUNDANE, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::MUNDANE, 1), Item::get(Item::GHAST_TEAR, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::MUNDANE, 1), Item::get(Item::GLISTERING_MELON, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::MUNDANE, 1), Item::get(Item::BLAZE_POWDER, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::MUNDANE, 1), Item::get(Item::MAGMA_CREAM, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::MUNDANE, 1), Item::get(Item::SUGAR, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::MUNDANE, 1), Item::get(Item::SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::MUNDANE, 1), Item::get(Item::RABBIT_FOOT, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::REGENERATION, 1), Item::get(Item::GHAST_TEAR, 0, 1), Item::get(Item::SPLASH_POTION, Potion::AWKWARD, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::LONG_REGENERATION, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::REGENERATION, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::STRONG_REGENERATION, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::REGENERATION, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::POISON, 1), Item::get(Item::SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::AWKWARD, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::LONG_POISON, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::POISON, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::STRONG_POISON, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::POISON, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::HEALING, 1), Item::get(Item::GLISTERING_MELON, 0, 1), Item::get(Item::SPLASH_POTION, Potion::AWKWARD, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::STRONG_HEALING, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::HEALING, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::WATER_BREATHING, 1), Item::get(Item::PUFFERFISH, 0, 1), Item::get(Item::SPLASH_POTION, Potion::AWKWARD, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::LONG_WATER_BREATHING, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER_BREATHING, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::SWIFTNESS, 1), Item::get(Item::SUGAR, 0, 1), Item::get(Item::SPLASH_POTION, Potion::AWKWARD, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::LONG_SWIFTNESS, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::SWIFTNESS, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::STRONG_SWIFTNESS, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::SWIFTNESS, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::FIRE_RESISTANCE, 1), Item::get(Item::MAGMA_CREAM, 0, 1), Item::get(Item::SPLASH_POTION, Potion::AWKWARD, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::LONG_FIRE_RESISTANCE, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::FIRE_RESISTANCE, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::LEAPING, 1), Item::get(Item::RABBIT_FOOT, 0, 1), Item::get(Item::SPLASH_POTION, Potion::AWKWARD, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::LONG_LEAPING, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::LEAPING, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::STRONG_LEAPING, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::LEAPING, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::SLOWNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::FIRE_RESISTANCE, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::SLOWNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::SWIFTNESS, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::SLOWNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::LEAPING, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::LONG_SLOWNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::LONG_FIRE_RESISTANCE, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::LONG_SLOWNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::LONG_LEAPING, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::LONG_SLOWNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::LONG_SWIFTNESS, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::LONG_SLOWNESS, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::SLOWNESS, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::NIGHT_VISION, 1), Item::get(Item::GOLDEN_CARROT, 0, 1), Item::get(Item::SPLASH_POTION, Potion::AWKWARD, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::LONG_NIGHT_VISION, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::NIGHT_VISION, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::INVISIBILITY, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::NIGHT_VISION, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::LONG_INVISIBILITY, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::INVISIBILITY, 1)));
        $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::LONG_INVISIBILITY, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::LONG_NIGHT_VISION, 1)));
        $ref = new ReflectionClass(Potion::class);
        $potions = array_diff_assoc($ref->getConstants(), $ref->getParentClass()->getConstants());
        foreach ($potions as $potion) {
            $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, $potion, 1), Item::get(Item::GUNPOWDER, 0, 1), Item::get(Item::POTION, $potion, 1)));
            $this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::LINGERING_POTION, $potion, 1), Item::get(Item::DRAGON_BREATH, 0, 1), Item::get(Item::SPLASH_POTION, $potion, 1)));
        }
    }

    /**
     * @param BrewingRecipe $recipe
     */
    public function registerBrewingRecipe(BrewingRecipe $recipe): void
    {
        $input = $recipe->getInput();
        $potion = $recipe->getPotion();
        $this->brewingRecipes[$input->getId() . ":" . ($input->getDamage() ?? "0") . ":" . $potion->getId() . ":" . ($potion->getDamage() ?? "0")] = $recipe;
    }

    /**
     * @param Item $input
     * @param Item $potion
     *
     * @return BrewingRecipe
     */
    public function matchBrewingRecipe(Item $input, Item $potion): ?BrewingRecipe
    {
        $subscript = $input->getId() . ":" . ($input->getDamage() ?? "0") . ":" . $potion->getId() . ":" . ($potion->getDamage() ?? "0");
        return $this->brewingRecipes[$subscript] ?? null;
    }

    /**
     * @param Entity $entity
     */


    /**
     * @param Entity $entity
     *
     * @return bool
     */
    public static function canStack(Entity $entity): bool
    {
        return $entity instanceof Living and (!$entity instanceof Human);
    }

    /**
     * @param Living $entity
     */
    public static function addToStack(Living $entity): void
    {
        $bb = $entity->getBoundingBox()->expandedCopy(12, 12, 12);
        foreach ($entity->getLevel()->getNearbyEntities($bb) as $e) {
            if ($e->namedtag->hasTag(self::STACK_TAG) and $e instanceof Living and $e->getName() === $entity->getName()) {
                $entity->flagForDespawn();
                self::increaseStackSize($e);
                return;
            }
        }
        self::setStackSize($entity);
    }

    /**
     * @param Living $entity
     * @param int $size
     *
     * @return bool
     */
    public static function setStackSize(Living $entity, int $size = 1): bool
    {
        $entity->namedtag->setInt(self::STACK_TAG, $size);
        if ($size < 1) {
            $entity->flagForDespawn();
            return false;
        }
        self::updateEntityName($entity);
        return true;
    }

    /**
     * @param Living $entity
     * @param int $size
     */
    public static function increaseStackSize(Living $entity, int $size = 1): void
    {
        if ($entity->namedtag !== null) {
            self::setStackSize($entity, $entity->namedtag->getInt(self::STACK_TAG, 0) + $size);
        }
    }

    /**
     * @param Living $entity
     * @param int $size
     */
    public static function decreaseStackSize(Living $entity, int $size = 1): void
    {
        if ($size > 0) {
            $currentSize = $entity->namedtag->getInt(self::STACK_TAG);
            $decr = min($size, $currentSize);
            $newSize = $currentSize - $decr;
            $level = $entity->getLevel();
            if (self::setStackSize($entity, $newSize)) {
                $entity->setHealth($entity->getMaxHealth());
            }
            for ($i = 0; $i < $decr; ++$i) {
                foreach ($entity->getDrops() as $item) {
                    $level->dropItem($entity, $item);
                }
            }
        }
    }

    /**
     * @param Living $entity
     */
    public static function updateEntityName(Living $entity): void
    {
        $entity->setNameTag(
            strtr(
                self::$nametag, [
                self::STACK_SIZE => $entity->namedtag->getInt(self::STACK_TAG),
                self::STACK_NAME => $entity->getName()
            ])
        );
    }

    /**
     * @return GlowstoneMountain
     */
    public function getGlowstoneMountain(): GlowstoneMountain
    {
        return $this->glowstoneMountain;
    }
}