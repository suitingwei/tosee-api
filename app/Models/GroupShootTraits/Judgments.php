<?php

namespace App\Models\GroupShootTraits;

use App\Models\GroupShoot;
use App\Models\Like;
use App\Models\MoneyGift;

trait Judgments
{

    /**
     * @return bool
     */
    public function isDeleted()
    {
        return $this->status == GroupShoot::STATUS_DELETED;
    }

    /**
     * @return bool
     */
    public function notDeleted()
    {
        return !$this->isDeleted();
    }

    /**
     * Is this group shoot owned by user.
     * @param $userId
     * @return bool
     */
    public function ownedByUser($userId)
    {
        return $this->owner_id == $userId;
    }

    /**
     * Is this group shoot's parent owned by user
     * @param $userId
     * @return bool
     */
    public function parentOwnedByUser($userId)
    {
        return self::where('owner_id', $userId)->where('id', $this->parent_id)->count() > 0;
    }

    /**
     * Determines whether the groupshoot is the parent shoot.
     * @return bool
     */
    public function isParentShoot()
    {
        return $this->parent_id <= 0;
    }

    /**
     * Determines whether the groupshoot is the merged shoot.
     * @return bool
     */
    public function isMergedShoot()
    {
        return $this->type == GroupShoot::TYPE_MERGED;
    }

    /**
     * @return bool
     */
    public function isChildNotMergedShoot()
    {
        return !$this->isParentShoot() && !$this->isMergedShoot();
    }

    /**
     * @return bool
     */
    public function hadShareRedBags()
    {
        if (!$this->isParentShoot()) {
            return false;
        }

        return $this->moneyGifts()->where('status', MoneyGift::STATUS_SHARE_GIFT_PAID)->count() > 0;
    }

    /**
     * @param $uid
     * @return bool
     */
    public function isLikedByUser($uid)
    {
        return Like::where('user_id', $uid)->where('value', $this->id)->count() > 0;
    }

    /**
     * @return mixed
     */
    public function likes_count()
    {
        return Like::where('value', $this->id)->count();
    }

    public function hadChildrenShoots()
    {
       return $this->childGroupShoots()->notDeleted()->exists();
    }
}
