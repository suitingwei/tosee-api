<?php

namespace App\Models\GroupShootTraits;


use App\Models\GroupShootRule;
use App\Models\MoneyGift;
use App\Models\User;
use App\Services\Helper;
use App\Services\QiniuService;
use App\Services\ShareService;
use App\Services\Strategies\GroupShootThumbNailShareV2;
use Illuminate\Support\Facades\Redis;

trait Mutators
{

    /**
     * @return string
     */
    public function getGifCoverUrlAttribute()
    {
        if ($this->attributes['gif_cover_url']) {
            return Helper::url('' . $this->attributes['gif_cover_url'], env('QINIU_APP_DOMAIN'));
        }
        return Helper::url('webp/' . $this->video_key, env('QINIU_APP_DOMAIN'));
    }

    public function getWebpCoverUrlAttribute()
    {
        return QiniuService::getPngFromGif($this->attributes['gif_cover_url']);
    }

    public function getSquareCoverUrlAttribute()
    {
        return QiniuService::getSquareWebpFromGif($this->attributes['gif_cover_url']);
    }

    /**
     * @return string
     */

    /**
     * @param int $type
     * @return string
     */
    public function getVideoUrlAttribute($type = 0)
    {
        if ($type == 1) {
            return Helper::url($this->video_key, env('QINIU_VIDEO_DOMAIN'));
        }

        if (Redis::sismember(QiniuService::STICKER_Id_KEY, $this->video_key)) {
            $path = 'sticker/' . $this->video_key;
        }
        elseif (!Redis::sismember(QiniuService::PERSISTENT_Id_KEY, $this->video_key)) {
            $path = 'watermark/' . $this->video_key;
        }
        else {
            $path = $this->video_key;
        }

        return Helper::url($path, env('QINIU_VIDEO_DOMAIN'));
    }

    /**
     * @return string
     */
    public function getOriginalVideoUrlAttribute()
    {
        return QiniuService::generateVideoUrlFromKey($this->attributes['original_video_key']);
    }

    /**
     * Get this parent group shoot's all taken money.
     * -----------------------------------------------
     * @return int
     */
    public function getTakenMoneyAttribute()
    {
        if (!($moneyGift = $this->sharedMoneyGift())) {
            return 0;
        }
        return $moneyGift->childMoneyGifts()->takenMoney()->sum('money');
    }

    /**
     * Get rewarded money from the parent group shoot.
     * @return int
     */
    public function getRewardedMoneyAttribute()
    {
        if ($this->isParentShoot()) {
            return 0;
        }

        $moneyGift = $this->moneyGifts->first();

        return $moneyGift ? (int)$moneyGift->money : 0;
    }

    /**
     * Get the merge video url.
     * @return string
     */
    public function getMergeVideoUrlAttribute()
    {
        return Helper::url('' . $this->video_key, env('QINIU_APP_DOMAIN'));
    }

    /**
     * Get the text share title.
     * @param null $shareUserId
     * @return string
     */
    public function getTextShareTitleAttribute($shareUserId = null)
    {
        if ($this->title) {
            return '一起玩群拍：' . $this->title;
        }

        if (is_null($shareUserId)) {
            return "与{$this->owner->nickname}一起玩群拍";
        }

        $shareUser = User::find($shareUserId);

        if (!$shareUser->hadJoinedGroupShoot($this)) {
            return "与{$this->owner->nickname}一起玩群拍";
        }

        return "与{$shareUser->nickname}一起玩群拍";
    }

    /**
     * Get the thumbnail share title,used for when  share a parent group shoot.
     * @return string
     */
    public function getThumbnailShareTitleAttribute()
    {
        $joinedUserCount = $this->joinedUsersCount();
        $moneyGift       = $this->sharedMoneyGift();

        if ($moneyGift instanceof MoneyGift) {
            return "一起群拍领红包：{$this->title}\n{$moneyGift->rmb_money}元红包等你来领！";
        }

        return "一起玩群拍：{$this->title}\n已有{$joinedUserCount}人一起群拍！";
    }

    /**
     * Get the share text.
     * @return string
     */
    public function getShareTextAttribute()
    {
        $moneyGift = $this->sharedMoneyGift();
        if ($moneyGift instanceof MoneyGift) {
            return "玩拍领红包：{$moneyGift->rmb_money}元红包等你来领取，已有{$this->taken_money_users_count}人领取，赶快抢！";
        }

        return '一起玩群拍，已有' . $this->joinedUsersCount() . '人一起群拍,赶快参加？';

    }

    /**
     * Get the mobile h5 url.
     * @return string
     */
    public function getMobileUrlAttribute()
    {
        return Helper::url("mp/groupshoots/{$this->id}", env('MOBILE_HOST'));
    }

    /**
     * @return string
     */
    public function getTextShareThumbnailUrlAttribute()
    {
        return QiniuService::getTextShareThumbnailUrl($this->video_key);
    }

    /**
     * This url will generate a merged covers image,used for share the groupshoot thunmbnail
     * @see https://app.zeplin.io/project/58c7ffdcff98945ac51ca72a/screen/58f72ffa2f0a014982be9eec
     * @param null $userId
     * @return string
     */
    public function getMergedCoversImageUrlAttribute($userId = null)
    {
        return Helper::url('v1/groupshoots/' . $this->id . '/merged-covers?user_id=' . $userId);
    }

    /**
     * This url will generate a groupshoot share image,with merged-covers,owner info, and group shoot info.
     * @see https://app.zeplin.io/project/58c7ffdcff98945ac51ca72a/screen/58f72ffa2f0a014982be9eec
     * @param null $userId
     * @return string
     */
    public function getShareGroupShootUrlAttribute($userId = null)
    {
        return Helper::url('v1/groupshoots/' . $this->id . '/share-cover?user_id=' . $userId);
    }

    /**
     * Get the groupshoot thunmbnail share url.
     * @see https://app.zeplin.io/project/58c7ffdcff98945ac51ca72a/screen/58f72ffa2f0a014982be9eec
     * @param null $shareUserId
     * @return string
     */
    public function getThumbnailShareUrlAttribute($shareUserId = null)
    {
        return ShareService::choosePolicy(new GroupShootThumbNailShareV2)->generateThunmbnailShareUrlForUser($this, $shareUserId);
    }

    /**
     * Generate the groupshoot share info.
     * @param null $shareUserId
     * @return array
     */
    public function getThumbnailShareInfoAttribute($shareUserId = null)
    {
        return ShareService::choosePolicy(new GroupShootThumbNailShareV2)->generateShareInfoForUser($this, $shareUserId);
    }

    public function getTemplateThumbnailUrlAttribute($shareUserId = null)
    {
        if ($this->ownedByUser($shareUserId)) {
            return $this->big_iframe_url;
        }

        $lastestChildGroupShoot = $this->childGroupShoots()->orderByDesc('created_at')->notDeleted()->notMerged()->first();
        return $lastestChildGroupShoot ? $lastestChildGroupShoot->big_iframe_url : $this->big_iframe_url;
    }

    /**
     * @return bool
     */
    public function isFrameGenerated()
    {
        $frameUrl = QiniuService::getVideoFrameUrl($this->video_key);

        $handle = curl_init($frameUrl);

        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($handle);
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        if ($httpCode == 404) {
            return false;
        }
        curl_close($handle);
        return true;
    }

    /**
     * Get groupshoot canvas direction.
     */
    public function getCanvasDirectionAttribute()
    {
        $rule = $this->rule;
        if (!$rule) {
            return GroupShootRule::CANVAS_DIRECTION_VERTICAL;
        }

        return $rule->canvas_direction;
    }

    /**
     * Taken largest money.
     * @return int
     */
    public function getTakenLargestMoneyAttribute()
    {
        $moneyGift = $this->sharedMoneyGift();
        if (!$moneyGift) {
            return 0;
        }

        $childMoneyGift = $moneyGift->childMoneyGifts()->takenMoney()->orderBy('money', 'desc')->first();

        return $childMoneyGift ? $childMoneyGift->money : 0;
    }

    public function getTakenMoneyUsersCountAttribute()
    {
        return $this->hadTakenMoneyUsers(true);
    }

    /**
     * Determines whether the children groupshoot is the luckiest,which taken the largest money of the parent's money gifts.
     * Remember, the luckiest should only be true, after all money gifts have been taken.
     * @param null $parentShootLargestTakenMoney
     * @return bool
     */
    public function getIsLuckiestAttribute($parentShootLargestTakenMoney = null)
    {
        //The parent shoot is always NOT be the luckiest,because the money gifts only avaible after parent groupshoot created.
        if ($this->isParentShoot()) {
            return false;
        }

        $parentMoneyGift = $this->parent->sharedMoneyGift();

        //If the parent groupshoot has not share the money gifts, there is no red bags.
        if (!($parentMoneyGift instanceof MoneyGift)) {
            return false;
        }

        //If there is still money gift NOT taken, there is not luckiest.
        if (!$parentMoneyGift->allTaken()) {
            return false;
        }

        //We pass the parent groupshoot's largest taken money, because this method will be called on every loop of the chidren group shoots,
        //when formatter the groupshoots,So we can save the time.
        if ($parentShootLargestTakenMoney) {
            return $this->rewardedMoney == $parentShootLargestTakenMoney;
        }

        return $this->rewardedMoney == $this->parent->taken_largest_money;
    }

    /**
     * Determines whether the parent groupshoot's red bags is lucky type or avarage type.
     * @return boolean
     */
    public function getIsLuckyRedBagAttribute()
    {
        $moneyGift = $this->sharedMoneyGift();

        if (!$moneyGift) {
            return false;
        }

        return $moneyGift->isLucky();
    }

    /**
     * Get the sample frame urls which generated by the client side.
     * @return array
     */
    public function getSampleFrameUrlsAttribute()
    {
        return collect(explode(',', $this->attributes['sample_frame_keys']))->map(function ($key) {
            return QiniuService::getSquareWebpFromGif($key);
        })->all();
    }

    /**
     * Get the group shoot's rules.
     */
    public function getRulesAttribute()
    {
        if (!($rule = $this->rule)) {
            return new \stdClass();
        }

        return $rule->formatSelf();
    }

    public function getTitleAttribute()
    {
        if ($this->isParentShoot()) {
            return $this->attributes['title'];
        }
        return $this->parent->title;
    }

    public function getTemplateIdAttribute()
    {
        if (!$this->isParentShoot()) {
            return $this->parent->template_id;
        }

        if (!($rule = $this->rule)) {
            return 0;
        }
        return $rule->template_id;
    }

    public function getHadLeftRedBagsAttribute()
    {
        if (!$this->hadShareRedBags()) {
            return false;
        }

        return $this->taken_money_users_count < $this->moneyGifts->count();
    }

    public function getVframeCoverUrlAttribute()
    {
        return QiniuService::getVideoFrameUrlImmedidate($this->attributes['original_video_key']);
    }

    public function getIsFirstJoinAttribute()
    {
        if ($this->isParentShoot()) {
            return false;
        }
        $firstJoinShoot = $this->parent->childGroupShoots()->notDeleted()->orderBy('created_at', 'asc')->first();
        return $firstJoinShoot ? ($this->id == $firstJoinShoot->id) : false;
    }

    public function getBigIframeUrlAttribute()
    {
        return QiniuService::getPngFromGif($this->attributes['big_iframe_url']);
//        if ($this->isParentShoot()) {
//            return QiniuService::getHttpsVideoUrl($this->attributes['big_iframe_url']);
//        }
//        return $this->parent->big_iframe_url;
    }
}
