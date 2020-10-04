<?php

namespace hcf\network\packets;

use hcf\network\NetworkInventoryAction;
use stdClass;
use UnexpectedValueException;

class InventoryTransactionPacket extends \pocketmine\network\mcpe\protocol\InventoryTransactionPacket {

    protected function decodePayload(): void {
        $this->transactionType = $this->getUnsignedVarInt();
        for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i) {
            $this->actions[] = (new NetworkInventoryAction())->read($this);
        }
        $this->trData = new stdClass();
        switch($this->transactionType) {
            case self::TYPE_NORMAL:
            case self::TYPE_MISMATCH:
                //Regular ComplexInventoryTransaction doesn't read any extra data
                break;
            case self::TYPE_USE_ITEM:
                $this->trData->actionType = $this->getUnsignedVarInt();
                $this->getBlockPosition($this->trData->x, $this->trData->y, $this->trData->z);
                $this->trData->face = $this->getVarInt();
                $this->trData->hotbarSlot = $this->getVarInt();
                $this->trData->itemInHand = $this->getSlot();
                $this->trData->playerPos = $this->getVector3();
                $this->trData->clickPos = $this->getVector3();
                $this->trData->blockRuntimeId = $this->getUnsignedVarInt();
                break;
            case self::TYPE_USE_ITEM_ON_ENTITY:
                $this->trData->entityRuntimeId = $this->getEntityRuntimeId();
                $this->trData->actionType = $this->getUnsignedVarInt();
                $this->trData->hotbarSlot = $this->getVarInt();
                $this->trData->itemInHand = $this->getSlot();
                $this->trData->playerPos = $this->getVector3();
                $this->trData->clickPos = $this->getVector3();
                break;
            case self::TYPE_RELEASE_ITEM:
                $this->trData->actionType = $this->getUnsignedVarInt();
                $this->trData->hotbarSlot = $this->getVarInt();
                $this->trData->itemInHand = $this->getSlot();
                $this->trData->headPos = $this->getVector3();
                break;
            default:
                throw new UnexpectedValueException("Unknown transaction type $this->transactionType");
        }
    }

    protected function encodePayload(): void {
        $this->putUnsignedVarInt($this->transactionType);
        $this->putUnsignedVarInt(count($this->actions));
        foreach($this->actions as $action) {
            $action->write($this);
        }
        switch($this->transactionType) {
            case self::TYPE_NORMAL:
            case self::TYPE_MISMATCH:
                break;
            case self::TYPE_USE_ITEM:
                $this->putUnsignedVarInt($this->trData->actionType);
                $this->putBlockPosition($this->trData->x, $this->trData->y, $this->trData->z);
                $this->putVarInt($this->trData->face);
                $this->putVarInt($this->trData->hotbarSlot);
                $this->putSlot($this->trData->itemInHand);
                $this->putVector3($this->trData->playerPos);
                $this->putVector3($this->trData->clickPos);
                $this->putUnsignedVarInt($this->trData->blockRuntimeId);
                break;
            case self::TYPE_USE_ITEM_ON_ENTITY:
                $this->putEntityRuntimeId($this->trData->entityRuntimeId);
                $this->putUnsignedVarInt($this->trData->actionType);
                $this->putVarInt($this->trData->hotbarSlot);
                $this->putSlot($this->trData->itemInHand);
                $this->putVector3($this->trData->playerPos);
                $this->putVector3($this->trData->clickPos);
                break;
            case self::TYPE_RELEASE_ITEM:
                $this->putUnsignedVarInt($this->trData->actionType);
                $this->putVarInt($this->trData->hotbarSlot);
                $this->putSlot($this->trData->itemInHand);
                $this->putVector3($this->trData->headPos);
                break;
            default:
                throw new UnexpectedValueException("Unknown transaction type $this->transactionType");
        }
    }
}