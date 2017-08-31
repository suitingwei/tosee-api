<?php

namespace App\Services\Strategies;

use App\Models\GroupShoot;
use App\Models\MoneyGift;
use App\Models\User;
use App\Services\Helper;
use Illuminate\Support\Facades\Redis;
use function Qiniu\base64_urlSafeEncode;

class GroupShootThunmNailShare
{
    protected $imageUrl;
    /**
     * @var GroupShoot
     */
    protected $groupShoot;
    protected $shareUserId;
    protected $fontColor;
    protected $chineseFont;

    const JOIN_GROUPSHOOT_BTN_URL    = 'http://v0.toseeapp.com/joined-groupshoot-btn.png';
    const CREATED_GROUPSHOOT_BTN_URL = 'http://v0.toseeapp.com/created-groupshoot-btn.png';

    const JOIN_GROUPSHOOT    = 0;
    const CREATED_GROUPSHOOT = 1;
    const NOT_JOIN_GROUPSHOOT ='NOT_JOIN_GROUP_SHOOT';

    /**
     * @param GroupShoot $groupShoot
     * @param            $shareUserId
     *
     * @return $this
     */
    protected function init(GroupShoot $groupShoot, $shareUserId)
    {
        $this->fontColor   = base64_urlSafeEncode('#FFF');
        $this->chineseFont = base64_urlSafeEncode('微软雅黑');
        $this->groupShoot  = $groupShoot;
        $this->shareUserId = $shareUserId;
        return $this;
    }

    protected function setBackgroundImageUrl()
    {
        return $this;
    }

    /**
     * @param GroupShoot $groupShoot
     *
     * @param            $shareUserId
     *
     * @return string
     */
    public function generateGroupShootShareImageUrl(GroupShoot $groupShoot, $shareUserId)
    {
        return $this->init($groupShoot, $shareUserId)
                    ->setBackgroundImageUrl()
                    ->enableWatermark3()
                    ->appendMergedCovers()
                    ->appendOwnerInfo()
                    ->appendGroupShootInfo()
                    ->appendQrCode()
                    ->appendAppInfo()
                    ->get();
    }

    protected function appendQrCode()
    {
        $qrcodeUrl      = base64_urlSafeEncode(Helper::url('qiniu/qrcode/' . base64_urlSafeEncode($this->groupShoot->mobile_url)));
        $this->imageUrl .= '/image/aHR0cDovL3MudG9zZWVhcHAuY29tL2xvZ28vcXJjb2RlLWJhY2tncm91bmQucG5n/gravity/SouthWest/dx/210/dy/50/ws/0.16';
        $this->imageUrl .= '/image/' . $qrcodeUrl . '/gravity/SouthWest/dx/214/dy/54/ws/0.15';
        $this->imageUrl .= '/image/aHR0cDovL3MudG9zZWVhcHAuY29tL2xvZ28vaWNvbng0Ni5wbmc=/gravity/SouthWest/dx/255/dy/90/ws/0.05';
        return $this;
    }

    protected function enableWatermark3()
    {
        $this->imageUrl .= '?watermark/3';
        return $this;
    }

    protected function appendOwnerInfo()
    {
        $nickname = $this->groupShoot->owner->nickname;
        $avatar   = $this->groupShoot->owner->avatar;

        $shareUser = User::find($this->shareUserId);
        //If the user had joined the parent groupshoot,append the share user.
        if ($hadShareUserJoinedGroupShoot = $shareUser->hadJoinedGroupShoot($this->groupShoot)) {
            $nickname = $shareUser->nickname;
            $avatar   = $shareUser->avatar;
        }

        $avatarToCircleUrl = Helper::url('v1/share/avatar-to-circle?avatar=' . base64_urlSafeEncode($avatar));
        $this->imageUrl    .= '/image/' . base64_urlSafeEncode($avatarToCircleUrl) . '/gravity/South/dx/0/dy/490/ws/0.11';
        $this->imageUrl    .= '/text/' . base64_urlSafeEncode($nickname) . '/fill/' . $this->fontColor . '/font/' . $this->chineseFont . '/fontsize/558/gravity/South/dx/0/dy/430';

        if ($this->groupShoot->ownedByUser($this->shareUserId)) {
            $joinOrCreateGroupShootBtnUrl = self::CREATED_GROUPSHOOT_BTN_URL;
        }
        else {
            $joinOrCreateGroupShootBtnUrl = $hadShareUserJoinedGroupShoot ? self::JOIN_GROUPSHOOT_BTN_URL : '';
        }
        $this->imageUrl .= '/image/' . base64_urlSafeEncode($joinOrCreateGroupShootBtnUrl) . '/gravity/South/dx/0/dy/380/ws/0.09';
        return $this;
    }

    protected function appendGroupShootInfo()
    {
        $title          = base64_urlSafeEncode($this->groupShoot->title);
        $this->imageUrl .= '/text/' . $title . '/fill/' . $this->fontColor . '/font/' . $this->chineseFont . '/fontsize/760/gravity/South/dx/0/dy/310';

        $moneyGift = $this->groupShoot->sharedMoneyGift();
        if ($moneyGift instanceof MoneyGift) {
            $receiveInfo    = base64_urlSafeEncode($moneyGift->receivedCount() . '人已领红包');
            $color          = base64_urlSafeEncode('#ffffff');
            $this->imageUrl .= '/text/' . $receiveInfo . '/fill/' . $color . '/font/' . $this->chineseFont . '/fontsize/560/gravity/SouthWest/dx/0/dy/260/dissolve/80';

            $redbagImageUrl = base64_urlSafeEncode('http://v0.toseeapp.com/red-bag.png');
            $this->imageUrl .= '/image/' . $redbagImageUrl . '/gravity/SouthWest/dx/250/dy/260/';
        }

        return $this;
    }

    protected function appendAppInfo()
    {
        $title          = base64_urlSafeEncode('长按参与群拍');
        $this->imageUrl .= '/text/' . $title . '/fill/' . $this->fontColor . '/font/' . $this->chineseFont . '/fontsize/460/gravity/South/dx/50/dy/140';

        $title          = base64_urlSafeEncode('抢红包');
        $color          = base64_urlSafeEncode('#ea4956');
        $this->imageUrl .= '/text/' . $title . '/fill/' . $color . '/font/' . $this->chineseFont . '/fontsize/520/gravity/South/dx/20/dy/100';

        $title          = base64_urlSafeEncode('视频由·TOSEE生成');
        $color          = base64_urlSafeEncode('#ffffff');
        $this->imageUrl .= '/text/' . $title . '/fill/' . $color . '/font/' . $this->chineseFont . '/fontsize/460/gravity/South/dx/80/dy/50/dissolve/40';

        return $this;
    }

    protected function appendMergedCovers()
    {
        //Get the merged covers image,Notice that this image will be generated by our application server,in this route.
        //Qiniu will cache the image based on the url,so we should update the url in 60s,incase the user get the newest cover.
        $mergedCoversUrl      = $this->groupShoot->getMergedCoversImageUrlAttribute($this->shareUserId);
        $cachedMergedCoverUrl = Redis::get($mergedCoversUrl);
        if ($cachedMergedCoverUrl) {
            $groupShootsMergedCoversUrl = $cachedMergedCoverUrl;
        }
        else {
            $groupShootsMergedCoversUrl = base64_urlSafeEncode($mergedCoversUrl . '?timestamp=' . time());
            Redis::setex($mergedCoversUrl, 60, $groupShootsMergedCoversUrl);
        }

        \Log::info('share service merged cover url ' . $groupShootsMergedCoversUrl);
        $this->imageUrl .= '/image/' . $groupShootsMergedCoversUrl . '/gravity/North/dx/0/dy/60';
        //Appent the play button image on the merged covers.
        $playButtonUrl  = base64_urlSafeEncode('http://v0.toseeapp.com/share-play-button.png');
        $this->imageUrl .= '/image/' . $playButtonUrl . '/gravity/North/dx/0/dy/310';
        return $this;
    }

    public function generateShareInfo(GroupShoot $groupShoot, $shareUserId)
    {
        $this->groupShoot  = $groupShoot;
        $this->shareUserId = $shareUserId;
        $shareUserInfo     = $this->getShareUserInfo();
        return [
            'covers'                  => $this->getShareMergeCovers(),
            'user_name'               => $shareUserInfo['user_name'],
            'avatar'                  => $shareUserInfo['avatar'],
            'joinOrCreate'            => $shareUserInfo['joinOrCreate'],
            'title'                   => $this->groupShoot->title,
            'taken_money_users_count' => $this->groupShoot->taken_money_users_count,
            'had_red_bags'            => $this->groupShoot->hadShareRedBags(),
        ];

    }

    protected function getShareMergeCovers()
    {
        $childGroupShoot = $this->groupShoot->childGroupShoots()->notMerged()->notDeleted()->createdBy($this->shareUserId)->orderBy('id', 'desc')->first();
        //If the user had joined the groupshoots, then use his last group shoot.
        if ($childGroupShoot) {
            return $childGroupShoot->sample_frame_urls;
        }

        //If the user had not joined the groupshoots,we'll chooose different share policy depends on the children group shoots.
        $childrenGroupShoots = $this->groupShoot->childGroupShoots()->notMerged()->notDeleted()->orderBy('id', 'desc')->get();

        if ($childrenGroupShoots->count() < 3) {
            //Otherwise,use the owner's last group shoot.
            $groupShoot = $this->groupShoot->childGroupShoots()->createdBy($this->groupShoot->owner_id)->notMerged()->notDeleted()->orderBy('id', 'desc')->first();

            //Incase the group shoot has no children group shoots,we should share the parent shoot.
            $groupShoot = $groupShoot ?: $this->groupShoot;
            //$imagesArray = QiniuService::getVideoSampleFrames($groupShoot->original_video_key);
            //$imagesArray = array_fill(0, 4, $groupShoot->gif_cover_url);
            $imagesArray = $groupShoot->sample_frame_urls;
        }
        else {
            $imagesArray = [];
            //If the children groupshoots have more than 3 counts,we'll choose 4 different groupshoots' cover to make the merged-cover.
            foreach ($childrenGroupShoots as $groupShoot) {
                array_push($imagesArray, $groupShoot->square_cover_url);
            }
            if (count($imagesArray) < 4) {
                array_push($imagesArray, $this->groupShoot->square_cover_url);
            }
        }
        return $imagesArray;
    }

    protected function get()
    {
        //return $this->imageUrl . '|imageslim';
        return $this->imageUrl;
    }

    private function getShareUserInfo()
    {
        $nickname = $this->groupShoot->owner->nickname;
        $avatar   = $this->groupShoot->owner->avatar;

        $shareUser = User::find($this->shareUserId);
        //If the user had joined the parent groupshoot,append the share user.
        if ($hadShareUserJoinedGroupShoot = $shareUser->hadJoinedGroupShoot($this->groupShoot)) {
            $nickname = $shareUser->nickname;
            $avatar   = $shareUser->avatar;
        }

        if ($this->groupShoot->ownedByUser($this->shareUserId)) {
            $joinOrCreate = self::CREATED_GROUPSHOOT;
        }
        else {
            $joinOrCreate = $hadShareUserJoinedGroupShoot ? self::JOIN_GROUPSHOOT : self::CREATED_GROUPSHOOT;
        }
        return [
            'user_name'    => $nickname,
            'avatar'       => $avatar,
            'joinOrCreate' => $joinOrCreate,
        ];
    }
}
