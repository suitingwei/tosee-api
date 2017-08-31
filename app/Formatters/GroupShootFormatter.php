<?php

namespace App\Formatter;

use App\Models\GroupShoot;

class GroupShootFormatter
{
    /**
     * Get parent shoot formatter .
     */
    public static function getShowFormatterWithBriefInfo()
    {
        return function (GroupShoot $parentGroupShoot) {
            $groupShootInfo = [
                'owner'                   => $parentGroupShoot->owner,
                'title'                   => $parentGroupShoot->title,
                'verify_code'             => $parentGroupShoot->verify_code,
                'joinCount'               => $parentGroupShoot->joinedUsersCount(),
                'merge_shoots_title'      => $parentGroupShoot->merge_shoots_title,
                'members'                 => $parentGroupShoot->joinedUsers(true, 5, true),
                'taken_money_users_count' => $parentGroupShoot->taken_money_users_count,
                'had_red_bags'            => (boolean)$parentGroupShoot->sharedMoneyGift(),
                'is_lucky_red_bag'        => $parentGroupShoot->is_lucky_red_bag,
                'had_left_red_bags'       => $parentGroupShoot->had_left_red_bags,
            ];

            if ($parentGroupShoot->hadShareRedBags()) {
                $groupShootInfo['moneyGiftCount'] = $parentGroupShoot->taken_money;
            }
            return $groupShootInfo;
        };
    }

    /**
     * @param $parentShootLargestTakenMoney
     * @return \Closure
     */
    public static function getShowFormatterForChildShoot($parentShootLargestTakenMoney = null)
    {
        return function (GroupShoot $groupShoot) use ($parentShootLargestTakenMoney) {
            return [
                'id'                 => $groupShoot->id,
                'title'              => $groupShoot->title,
                'nickname'           => $groupShoot->owner->nickname,
                'avatar_url'         => $groupShoot->owner->avatar,
                'music_key'          => $groupShoot->music_key,
                'webp_cover_url'     => $groupShoot->webp_cover_url,
                'gif_cover_url'      => $groupShoot->gif_cover_url,
                'big_iframe_url'      => $groupShoot->big_iframe_url,
                'merge_status'       => $groupShoot->merge_status,
                'video_url'          => $groupShoot->getVideoUrlAttribute(1),
                'original_video_url' => $groupShoot->original_video_url,
                'merge_video_url'    => $groupShoot->merge_video_url,
                'money_gift'         => $groupShoot->rewardedMoney,
                'is_liked'           => $groupShoot->isLikedByUser(app('request')->input('user_id') ?: $groupShoot->owner->id),
                'likes_count'        => $groupShoot->likes_count(),
//                'is_luckiest'        => $groupShoot->rewardedMoney == $parentShootLargestTakenMoney,
                'is_luckiest'        => $groupShoot->getIsLuckiestAttribute($parentShootLargestTakenMoney),
                'video_owner_uid'    => $groupShoot->owner_id,
                'is_first_join'      => $groupShoot->is_first_join,
            ];
        };
    }
}
