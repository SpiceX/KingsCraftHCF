<?php

namespace hcf\level\block;

use hcf\item\ItemIds;
use hcf\level\inventory\EnchantInventory;
use hcf\network\WindowIds;
use pocketmine\block\Block;
use pocketmine\block\BlockToolType;
use pocketmine\block\Transparent;
use pocketmine\item\Item;
use pocketmine\item\TieredTool;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\tile\EnchantTable as TileEnchantTable;
use pocketmine\tile\Tile;

class EnchantingTable extends Transparent {


    protected $id = self::ENCHANTING_TABLE;

    public function __construct(int $meta = 0){
        parent::__construct(Block::ENCHANTING_TABLE, $meta, 'Enchanting Table', ItemIds::ENCHANTING_TABLE);
        $this->meta = $meta;
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
        $this->getLevelNonNull()->setBlock($blockReplace, $this, true, true);

        Tile::createTile(Tile::ENCHANT_TABLE, $this->getLevelNonNull(), TileEnchantTable::createNBT($this, $face, $item, $player));

        return true;
    }

    public function getHardness() : float{
        return 5;
    }

    public function getBlastResistance() : float{
        return 6000;
    }

    public function getName() : string{
        return "Enchanting Table";
    }

    public function getToolType() : int{
        return BlockToolType::TYPE_PICKAXE;
    }

    public function getToolHarvestLevel() : int{
        return TieredTool::TIER_WOODEN;
    }

    /**
     * @param Item $item
     * @param Player|null $player
     *
     * @return bool
     */
    public function onActivate(Item $item, Player $player = null): bool {
        if($player instanceof Player) {
            //$this->getLevel()->setBlock($this, $this, true, true);
            //Tile::createTile(Tile::ENCHANT_TABLE, $this->getLevel(), EnchantTable::createNBT($this));
            $player->addWindow(new EnchantInventory($this), WindowIds::ENCHANT);
        }
        return true;
    }

    /**
     * @return int
     */
    public function countBookshelf(): int {
        $count = 0;
        $level = $this->getLevel();
        if ($level === null){
            return 0;
        }
        for($y = 0; $y <= 1; $y++) {
            for($x = -1; $x <= 1; $x++) {
                for($z = -1; $z <= 1; $z++) {
                    if($z === 0 && $x === 0) {
                        continue;
                    }
                    if($level->getBlock($this->add($x, 0, $z))->isTransparent() && $level->getBlock($this->add(0, 1, 0))->isTransparent()) {
                        if($level->getBlock($this->add($x << 1, $y, $z << 1))->getId() === Block::BOOKSHELF) {
                            $count++;
                        }
                        if($x !== 0 && $z !== 0) {
                            if($level->getBlock($this->add($x << 1, $y, $z))->getId() === Block::BOOKSHELF) {
                                ++$count;
                            }
                            if($level->getBlock($this->add($x, $y, $z << 1))->getId() === Block::BOOKSHELF) {
                                ++$count;
                            }
                        }
                    }
                }
            }
        }
        return $count;
    }
}