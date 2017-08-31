<?php

namespace App\Models;

use App\Models\GroupShootTraits\Judgments;
use App\Models\GroupShootTraits\Mutators;
use App\Models\GroupShootTraits\Notifications;
use App\Models\GroupShootTraits\RelationShips;
use App\Models\GroupShootTraits\Scopes;
use App\Models\GroupShootTraits\Statistics;
use App\Traits\ModelFinder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Redis;

/**
 * App\Models\GroupShoot
 * @property mixed          canvas_direction
 * @property mixed          video_key
 * @property mixed          merge_status
 * @property mixed          title
 * @property mixed          id
 * @property mixed          music_key
 * @property int            status
 * @property int            owner_id
 * @property int            parent_id
 * @property GroupShoot     parent
 * @property mixed          type
 * @property mixed          thumbnail_share_title
 * @property int            verify_code
 * @property string         video_url
 * @property string         webp_cover_url
 * @property string         square_cover_url
 * @property string         gif_cover_url
 * @property Collection     moneyGifts
 * @property int            taken_money
 * @property User           owner
 * @property Collection     childGroupShoots
 * @property string         vframe_cover_url
 * @property int            rewardedMoney
 * @property mixed          merge_video_url
 * @property mixed          original_video_url
 * @property mixed          text_share_title
 * @property mixed          mobile_url
 * @property mixed          merged_covers_image_url
 * @property GroupShootRule rule
 * @property int            taken_largest_money
 * @property mixed          merge_shoots_title
 * @property mixed          taken_money_users_count
 * @property mixed          is_luckiest
 * @property mixed          original_video_key
 * @property mixed          is_lucky_red_bag
 * @property mixed          template_id
 * @property mixed          had_left_red_bags
 * @property mixed          is_first_join
 * @property mixed          big_iframe_url
 */
class GroupShoot extends Model
{
    /**
     * Query scopes.
     */
    use Scopes;

    /**
     * Getters and setters.
     */
    use Mutators;

    /**
     * Things for judgements.
     */
    use Judgments;

    /**
     * Relationships.
     */
    use RelationShips;

    /**
     * Things about notification.
     * Such as push when new groupshoot created,or someone joined.
     */
    use Notifications;

    /**
     * Things about statictics.
     */
    use Statistics;

    /**
     * Eloquent model static find method,for IDE auto complettion.
     */
    use ModelFinder;

    const STATUS_NOT_DELETED                       = 1;  // Not deleted shoots.
    const STATUS_DELETED                           = 3;  // Deleted shoots.
    const TYPE_NOT_MERGED                          = 1;  //Not merged shoots.
    const TYPE_MERGED                              = 2;  //Merged shoots.
    const PARENT_SHOOT                             = 0; //The parent shoot.
    const IOS_JUMP_FROM_NOTIFICATION_TO_GROUPSHOOT = "tosee://groupshoot?id=";
    const GROUPSHOOT_VERIFY_CODE_REDIS_KEY         = 'groupshootverifycodes';

    /**
     * Rules for creating new group shoot.
     * @var array
     */
    public static $storeRules = [
        'video_key'          => 'required',
        'original_video_key' => 'string',
        'parent_id'          => 'required|numeric',
    ];

    public $guarded = [];

    /**
     * Listent the mode event.
     * 1.When group shoot created,dispatch the viedeo job into queue.
     */
    public static function boot()
    {
        parent::boot();

        /**
         * 1.Generate the video sticker.
         * 2.Create the share thumbnail.
         * 3.Generate the verify code for the parent group shoot.
         */
        static::created(function (GroupShoot $groupShoot) {
            if ($groupShoot->isParentShoot()) {
                $groupShoot->update(['verify_code' => self::makeVerifyCode()]);
            }
            $groupShoot->pushAccordingToType();
        });
    }

    /**
     * Make unique verify code.
     * @return int
     */
    private static function makeVerifyCode()
    {
        while (true) {
            $code = rand(100000, 999999);
            if (!Redis::sismember(self::GROUPSHOOT_VERIFY_CODE_REDIS_KEY, $code)) {
                Redis::sadd(self::GROUPSHOOT_VERIFY_CODE_REDIS_KEY, $code);
                return $code;
            }
        }
    }

    /**
     * Set the status to be deleted.
     */
    private function deleteShoot()
    {
        $this->update(['status' => self::STATUS_DELETED]);
    }

    /**
     * Delete the shoot, and all possible child shoots.
     */
    public function deleteWithChildGroupShoots()
    {
        $this->deleteShoot();
        $this->childGroupShoots()->update(['status' => self::STATUS_DELETED]);
    }

    /**
     * Determines whether the groupshoot has a shared money gift.
     * @return  MoneyGift|Collection
     */
    public function sharedMoneyGift()
    {
        return MoneyGift::where('group_shoot_id', $this->id)->where('status', MoneyGift::STATUS_SHARE_GIFT_PAID)->first();
    }

    /**
     * All joined group shoot's users.
     * The users ordered by the time they joined the group shoot.
     * The owner of the parent groupshoot should always be in the first place.
     * Whether the joined user's groupshoot still exists in the parent groupshoot.
     * @param bool         $withOwner
     * @param null|integer $count
     * @param bool         $onlyGroupShootExistsUsers
     * @return Collection
     */
    public function joinedUsers($withOwner = true, $count = null, $onlyGroupShootExistsUsers = false)
    {
        $joinedUserIdsQueryBuilder = $this->childGroupShoots()->where('owner_id', '!=', $this->owner_id);

        //Select those users whose groupshoots still exists in the parent group shoot's.
        if ($onlyGroupShootExistsUsers) {
            $joinedUserIdsQueryBuilder = $joinedUserIdsQueryBuilder->notDeleted();
        }

        $joinedUserIds = $joinedUserIdsQueryBuilder->pluck('owner_id')->unique()->prepend($this->owner_id);

        //remove the owner of the shoot from the result.
        if (!$withOwner) {
            $ownerId       = $this->owner_id;
            $joinedUserIds = $joinedUserIds->filter(function ($value) use ($ownerId) {
                return $value != $ownerId;
            });
        }

        if ($joinedUserIds->count() == 0) {
            return collect();
        }

        $usersQueryBuilder = User::whereIn('id', $joinedUserIds->all())->orderByRaw("field(id," . $joinedUserIds->implode(',') . ")");

        return is_null($count) ? $usersQueryBuilder->get() : $usersQueryBuilder->take($count)->get();
    }

    /**
     * Get all users' who have taken the money from the groupshoot.
     * If the selecteNumbersOnly is set to true, we'll only return the number of the users rather than a collection.
     * @param bool $selectNumbersOnly
     * @return \Illuminate\Database\Eloquent\Collection|Collection|int
     */
    public function hadTakenMoneyUsers($selectNumbersOnly = false)
    {
        $moneyGift = $this->sharedMoneyGift();

        if (!$moneyGift) {
            return $selectNumbersOnly ? 0 : collect();
        }

        $takenMoneyUserIds = $moneyGift->childMoneyGifts()->takenMoney()->pluck('owner_id')->unique();

        if ($selectNumbersOnly) {
            return $takenMoneyUserIds->count();
        }

        return User::whereIn('id', $takenMoneyUserIds->all())->get();
    }
}
