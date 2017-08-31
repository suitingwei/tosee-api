<?php

namespace App\Models\GroupShootTraits;

use App\Models\GroupShoot;
use App\Models\GroupShootRule;
use App\Models\MoneyGift;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait RelationShips
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function moneyGifts()
    {
        return $this->hasMany(MoneyGift::class);
    }

    /**
     * A groupshoot may belongs to a parent group shoot.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(GroupShoot::class, 'parent_id', 'id');
    }

    /**
     * A groupshoot(only parent shoot) may have a group shoot rule.
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function rule()
    {
        return $this->hasOne(GroupShootRule::class);
    }

    /**
     * A group shoot may have many child group shoots.
     * @return HasMany
     */
    public function childGroupShoots()
    {
        return $this->hasMany(GroupShoot::class, 'parent_id', 'id');
    }
}
