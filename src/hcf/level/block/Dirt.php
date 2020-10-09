<?php


namespace hcf\level\block;


use hcf\item\types\Crowbar;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockToolType;
use pocketmine\block\Solid;
use pocketmine\item\Hoe;
use pocketmine\item\Item;
use pocketmine\Player;

class Dirt extends Solid
{

    protected $id = self::DIRT;

    public function __construct(int $meta = 0){
        $this->meta = $meta;
    }

    public function getHardness() : float{
        return 0.5;
    }

    public function getToolType() : int{
        return BlockToolType::TYPE_SHOVEL;
    }

    public function getName() : string{
        if($this->meta === 1){
            return "Coarse Dirt";
        }
        return "Dirt";
    }

    public function onActivate(Item $item, Player $player = null) : bool{
        if($item instanceof Hoe || $item instanceof Crowbar || $item->getId() === Item::DIAMOND_HOE){
            $item->applyDamage(1);
            if($this->meta === 1){
                $this->getLevelNonNull()->setBlock($this, BlockFactory::get(Block::GRASS), true);
            }else{
                $this->getLevelNonNull()->setBlock($this, BlockFactory::get(Block::GRASS), true);
            }

            return true;
        }

        return false;
    }
}