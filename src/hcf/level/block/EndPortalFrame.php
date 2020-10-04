<?php

namespace hcf\level\block;

use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\Player;

class EndPortalFrame extends \pocketmine\block\EndPortalFrame {

    /**
     * EndPortalFrame constructor.
     *
     * @param int $meta
     */
    public function __construct($meta = 0) {
        parent::__construct($meta);
    }

    /**
     * @param Item $item
     *
     * @return bool
     */
    public function isBreakable(Item $item): bool {
        return false;
    }

    /**
     * @param Item $item
     * @param Block $block
     * @param Block $target
     * @param int $face
     * @param Vector3 $facePos
     * @param Player|null $player
     *
     * @return bool
     */
    public function place(Item $item, Block $block, Block $target, int $face, Vector3 $facePos, Player $player = null): bool {
        $this->getLevel()->setBlock($block, $this, true, true);
        return true;
    }

    /**
     * @param Item $item
     * @param Player|null $player
     *
     * @return bool
     */
    public function onActivate(Item $item, Player $player = null): bool {
        if(($this->getDamage() & 0x04) === 0 && $player instanceof Player && $item->getId() === Item::ENDER_EYE) {
            $this->setDamage(4);
            $this->getLevel()->setBlock($this, $this, true, true);
            $corners = $this->isValidPortal();
            if(is_array($corners)) {
                $corners = [
                    $corners[0]->getX(),
                    $corners[3]->getX(),
                    $corners[0]->getZ(),
                    $corners[3]->getZ(),
                    $corners[0]->getY()
                ];
                $this->createPortal($corners);
            }
            return true;
        }
        return false;
    }

    /**
     * @return Vector3[]|null
     */
    public function isValidPortal(): ?array {
        $minX = null;
        $maxX = null;
        $count = 0;
        for($x = $this->x - 2; $x <= $this->x + 2; $x++) {
            $block = $this->getLevel()->getBlock(new Vector3($x, $this->y, $this->z));
            if($count > 0 and ($block->getId() !== Block::END_PORTAL_FRAME xor $block->getDamage() !== 4)) {
                break;
            }
            if($block->getId() === Block::END_PORTAL_FRAME and $block->getDamage() === 4) {
                ++$count;
            }
            if($count === 1) {
                $minX = $x;
                continue;
            }
            if($count === 3) {
                $maxX = $x;
                break;
            }
        }
        if($count !== 3) {
            $minX = null;
            $maxX = null;
            $minZ = null;
            $maxZ = null;
            $count = 0;
            for($z = $this->z - 2; $z <= $this->z + 2; $z++) {
                $block = $this->getLevel()->getBlock(new Vector3($this->x, $this->y, $z));
                if($count > 0 and ($block->getId() !== Block::END_PORTAL_FRAME xor $block->getDamage() !== 4)) {
                    break;
                }
                if($block->getId() === Block::END_PORTAL_FRAME and $block->getDamage() === 4) {
                    ++$count;
                }
                if($count === 1) {
                    $minZ = $z;
                    continue;
                }
                if($count === 3) {
                    $maxZ = $z;
                    break;
                }
            }
        }
        if($count !== 3) {
            return null;
        }
        if($minX !== null and $maxX !== null) {
            $count = 0;
            $z = null;
            for($x = $minX; $x <= $maxX; $x++) {
                $block = $this->getLevel()->getBlock(new Vector3($x, $this->y, $this->z + 4));
                if($count > 0 and ($block->getId() !== Block::END_PORTAL_FRAME xor $block->getDamage() !== 4)) {
                    break;
                }
                if($block->getId() === Block::END_PORTAL_FRAME and $block->getDamage() === 4) {
                    ++$count;
                }
            }
            if($count === 3) {
                $z = $this->z + 4;
            }
            else {
                for($x = $minX; $x <= $maxX; $x++) {
                    $block = $this->getLevel()->getBlock(new Vector3($x, $this->y, $this->z - 4));
                    if($count > 0 and ($block->getId() !== Block::END_PORTAL_FRAME xor $block->getDamage() !== 4)) {
                        break;
                    }
                    if($block->getId() === Block::END_PORTAL_FRAME and $block->getDamage() === 4) {
                        ++$count;
                    }
                }
                if($count === 3) {
                    $z = $this->z - 4;
                }
                else {
                    return null;
                }
            }
        }
        elseif(isset($minZ) and isset($maxZ) and $minZ !== null and $maxZ !== null) {
            $count = 0;
            $x = null;
            for($z = $minZ; $z <= $maxZ; $z++) {
                $block = $this->getLevel()->getBlock(new Vector3($this->x + 4, $this->y, $z));
                if($count > 0 and ($block->getId() !== Block::END_PORTAL_FRAME xor $block->getDamage() !== 4)) {
                    break;
                }
                if($block->getId() === Block::END_PORTAL_FRAME and $block->getDamage() === 4) {
                    ++$count;
                }
            }
            if($count === 3) {
                $x = $this->x + 4;
            }
            else {
                for($z = $minZ; $z <= $maxZ; $z++) {
                    $block = $this->getLevel()->getBlock(new Vector3($this->x - 4, $this->y, $z));
                    if($count > 0 and ($block->getId() !== Block::END_PORTAL_FRAME xor $block->getDamage() !== 4)) {
                        break;
                    }
                    if($block->getId() === Block::END_PORTAL_FRAME and $block->getDamage() === 4) {
                        ++$count;
                    }
                }
                if($count === 3) {
                    $x = $this->x - 4;
                }
                else {
                    return null;
                }
            }
        }
        if(isset($minZ) and isset($maxZ) and isset($x) and $minZ !== null and $maxZ !== null) {
            for($i = min($this->x, $x) + 1; $i <= max($this->x, $x) - 1; $i++) {
                $block = $this->getLevel()->getBlock(new Vector3($i, $this->y, $minZ - 1));
                if($block->getId() !== Block::END_PORTAL_FRAME xor $block->getDamage() !== 4) {
                    return null;
                }
                $block = $this->getLevel()->getBlock(new Vector3($i, $this->y, $maxZ + 1));
                if($block->getId() !== Block::END_PORTAL_FRAME xor $block->getDamage() !== 4) {
                    return null;
                }
            }
        }
        if(isset($minX) and isset($maxX) and isset($z) and $minX !== null and $maxX !== null) {
            for($i = min($this->z, $z) + 1; $i <= max($this->z, $z) -1; $i++) {
                $block = $this->getLevel()->getBlock(new Vector3($minX - 1, $this->y, $i));
                if($block->getId() !== Block::END_PORTAL_FRAME xor $block->getDamage() !== 4) {
                    return null;
                }
                $block = $this->getLevel()->getBlock(new Vector3($maxX + 1, $this->y, $i));
                if($block->getId() !== Block::END_PORTAL_FRAME xor $block->getDamage() !== 4) {
                    return null;
                }
            }
        }
        $corners = [];
        if(isset($x) and isset($minZ) and isset($maxZ)) {
            $corners[0] = new Vector3(max($x, $this->x) - 1, $this->y, $minZ);
            $corners[1] = new Vector3(max($x, $this->x) - 1, $this->y, $maxZ);
            $corners[2] = new Vector3(min($x, $this->x) + 1, $this->y, $minZ);
            $corners[3] = new Vector3(min($x, $this->x) + 1, $this->y, $maxZ);
        }
        elseif(isset($z) and isset($minX) and isset($maxX)) {
            $corners[0] = new Vector3($minX, $this->y, max($z, $this->z) - 1);
            $corners[1] = new Vector3($maxX, $this->y, max($z, $this->z) - 1);
            $corners[2] = new Vector3($minX, $this->y, min($z, $this->z) + 1);
            $corners[3] = new Vector3($maxX, $this->y, min($z, $this->z) + 1);
        }
        return $corners;
    }

    /**
     * @param array|null $corners
     *
     * @return bool
     */
    private function createPortal(array $corners = null): bool {
        if($corners === null) {
            return false;
        }
        $x1 = min($corners[0], $corners[1]);
        $x2 = max($corners[0], $corners[1]);
        $z1 = min($corners[2], $corners[3]);
        $z2 = max($corners[2], $corners[3]);
        $y = $corners[4];
        for($curX = $x1; $curX <= $x2; $curX++) {
            for($curZ = $z1; $curZ <= $z2; $curZ++) {
                $pos = new Vector3($curX, $y, $curZ);
                $this->getLevel()->setBlock($pos, Block::get(Block::END_PORTAL), false, false);
            }
        }
        return true;
    }

    /**
     * @param array|null $corners
     */
    public function destroyPortal(array $corners = null): void {
        if($corners === null) {
            return;
        }
        $x1 = min($corners[0], $corners[1]);
        $x2 = max($corners[0], $corners[1]);
        $z1 = min($corners[2], $corners[3]);
        $z2 = max($corners[2], $corners[3]);
        $y = $corners[4];
        for($curX = $x1; $curX <= $x2; $curX++) {
            for($curZ = $z1; $curZ <= $z2; $curZ++) {
                $pos = new Vector3($curX, $y, $curZ);
                $this->getLevel()->setBlock($pos, Block::get(Block::AIR), false, false);
            }
        }
        return;
    }
}