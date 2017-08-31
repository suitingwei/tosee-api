<?php

namespace App\Models\GroupShootTraits;

use App\Models\GroupShoot;

trait Scopes
{

    /** * @param $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotDeleted($query)
    {
        return $query->where('status', GroupShoot::STATUS_NOT_DELETED);
    }

    /**
     * @param $query
     *
     * @return mixed
     */
    public function scopeDeleted($query)
    {
        return $query->where('status', GroupShoot::STATUS_DELETED);
    }

    /**
     * @param $query
     *
     * @return mixed
     */
    public function scopeParentShoot($query)
    {
        return $query->where('parent_id', GroupShoot::PARENT_SHOOT);
    }

    /**
     * @param $query
     *
     * @return mixed
     */
    public function scopeNotParentShoot($query)
    {
        return $query->where('parent_id', '!=', GroupShoot::PARENT_SHOOT);
    }

    /**
     * @param $query
     * @param $userId
     *
     * @return mixed
     */
    public function scopeCreatedBy($query, $userId)
    {
        return $query->where('owner_id', $userId);
    }

    /**
     * Get not merged group shoot.
     *
     * @param $query
     *
     * @return mixed
     */
    public function scopeNotMerged($query)
    {
        return $query->where('type', GroupShoot::TYPE_NOT_MERGED);
    }

    /**
     * Get merged group shoot.
     *
     * @param $query
     *
     * @return mixed
     */
    public function scopeMerged($query)
    {
        return $query->where('type', GroupShoot::TYPE_MERGED);
    }
}
