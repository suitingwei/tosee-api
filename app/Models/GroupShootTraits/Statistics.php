<?php

namespace App\Models\GroupShootTraits;

use App\Models\GroupShoot;

trait Statistics
{

    /**
     * @param $userId
     *
     * @return mixed
     */
    public static function joinedParentShootsCount($userId)
    {
        return GroupShoot::notMerged()
                         ->createdBy($userId)
                         ->notDeleted()
                         ->notParentShoot()
                         ->selectRaw('distinct parent_id')
                         ->pluck('parent_id')
                         ->map(function ($parentId) use ($userId) {
                             $groupShoot = GroupShoot::find($parentId);
                             return ($groupShoot->isDeleted() || $groupShoot->ownedByUser($userId)) ? 0 : 1;
                         })
                         ->sum();
    }

    /**
     * User's joined all parent shoots' joined users count total sum.
     * Excepts those user joined themselves created group shoots.
     *
     * @param $userId
     *
     * @return int
     */
    public static function joinedAllParentShootTotalUsersCount($userId)
    {
        return GroupShoot::createdBy($userId)
                         ->notMerged()
                         ->notDeleted()
                         ->notParentShoot()
                         ->selectRaw('distinct parent_id')
                         ->pluck('parent_id')
                         ->map(function ($parentId) use ($userId) {
                             $groupShoot = GroupShoot::find($parentId);
                             return ($groupShoot->isDeleted() || $groupShoot->ownedByUser($userId)) ? 0 : $groupShoot->joinedUsersCount();
                         })
                         ->sum();
    }

    /**
     * Get this parent groupshoot's all joined users count.
     * -------------------------------------------------------
     * 1.Includes the children shoots.
     * 2.Include the owner of the parent shoot.
     * 3.Distincted.
     *
     * @return int
     */
    public function joinedUsersCount()
    {
        return GroupShoot::where('parent_id', $this->id)
                         ->notMerged()
                         ->notDeleted()
                         ->selectRaw('distinct owner_id')
                         ->pluck('owner_id')
                         ->push($this->owner_id)
                         ->unique()
                         ->count();
    }
}
