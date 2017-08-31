<?php

namespace App\Services;


use App\Interfaces\GroupShootThumbNailShare;
use App\Models\GroupShoot;
use App\Services\Strategies\GroupShootThunmNailShare;

class ShareService
{
    /**
     * @var GroupShootThumbNailShare
     */
    private $groupshootThumbnailSharePolicy;

    /**
     * ShareService constructor.
     *
     * @param GroupShootThumbNailShare $groupShootThumbNailSharePolicy
     */
    private function __construct(GroupShootThunmNailShare $groupShootThumbNailSharePolicy)
    {
        $this->groupshootThumbnailSharePolicy = $groupShootThumbNailSharePolicy;
    }

    /**
     * @param GroupShootThumbNailShare $groupShootThumbNailSharePolicy
     *
     * @return ShareService
     */
    public static function choosePolicy(GroupShootThunmNailShare $groupShootThumbNailSharePolicy)
    {
        $instance = new self($groupShootThumbNailSharePolicy);

        return $instance;
    }

    public function generateShareInfoForUser(GroupShoot $groupShoot, $shareUserId)
    {
        return $this->groupshootThumbnailSharePolicy->generateShareInfo($groupShoot, $shareUserId);
    }

    /**
     * @param GroupShoot $groupShoot
     * @param            $shareUserId
     *
     * @return string
     */
    public function generateThunmbnailShareUrlForUser(GroupShoot $groupShoot, $shareUserId)
    {
        return $this->groupshootThumbnailSharePolicy->generateGroupShootShareImageUrl($groupShoot, $shareUserId);
    }

}
