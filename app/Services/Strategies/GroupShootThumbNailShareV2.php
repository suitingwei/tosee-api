<?php

namespace App\Services\Strategies;

use App\Models\MoneyGift;
use App\Models\User;
use App\Services\Helper;
use Redis;
use function Qiniu\base64_urlSafeEncode;

class GroupShootThumbNailShareV2 extends GroupShootThunmNailShare
{
    /**
     * @return $this
     */
    protected function setBackgroundImageUrl()
    {
        if ($this->groupShoot->sharedMoneyGift()) {
            $this->imageUrl = 'http://v0.toseeapp.com/share-background-v2.jpg';
        }
        else {
            $this->imageUrl = 'http://v0.toseeapp.com/share-background-no-redbags-v2.jpg';
        }
        $this->fontColor = base64_urlSafeEncode('#101011');
        return $this;
    }

    /**
     * This version's app info has been added into the background image.
     * @return $this
     */
    protected function appendAppInfo()
    {
        return $this;
    }

    /*t *
     * @return $this
     */
    protected function appendQrCode()
    {
        $qrcodeUrl = base64_urlSafeEncode(Helper::url('qiniu/qrcode/' . base64_urlSafeEncode($this->groupShoot->mobile_url)));
        //append the qrcode url
        $this->imageUrl .= '/image/' . $qrcodeUrl . '/gravity/SouthWest/dx/95/dy/165/ws/0.14';
        return $this;
    }

    /**
     * @return $this
     */
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
            $groupShootsMergedCoversUrl = base64_urlSafeEncode($mergedCoversUrl . '&timestamp=' . time());
            Redis::setex($mergedCoversUrl, 60, $groupShootsMergedCoversUrl);
        }

        $this->imageUrl .= '/image/' . $groupShootsMergedCoversUrl . '/gravity/North/dx/0/dy/84/ws/0.865';
        //Appent the play button image on the merged covers.
        $playButtonUrl  = base64_urlSafeEncode('http://v0.toseeapp.com/share-play-button-v2.png');
        $this->imageUrl .= '/image/' . $playButtonUrl . '/gravity/North/dx/0/dy/350';
        return $this;
    }

    /**
     * @return $this
     */
    protected function appendOwnerInfo()
    {
        $nickname = $this->groupShoot->owner->nickname;
        $avatar   = $this->groupShoot->owner->avatar;

        $shareUser                    = User::find($this->shareUserId);
        $hadShareUserJoinedGroupShoot = $shareUser ? $shareUser->hadJoinedGroupShoot($this->groupShoot) : false;

        //If the user had joined the parent groupshoot,append the share user.
        if ($hadShareUserJoinedGroupShoot) {
            $nickname = $shareUser->nickname;
            $avatar   = $shareUser->avatar;
        }

        $avatarToCircleUrl = Helper::url('v1/share/avatar-to-circle?avatar=' . base64_urlSafeEncode($avatar));
        $this->imageUrl    .= '/image/' . base64_urlSafeEncode($avatarToCircleUrl) . '/gravity/SouthWest/dx/95/dy/490/ws/0.11';
        $this->imageUrl    .= '/text/' . base64_urlSafeEncode($nickname) . '/fill/' . $this->fontColor . '/font/' . $this->chineseFont . '/fontsize/558/gravity/SouthWest/dx/195/dy/525';

        if ($this->groupShoot->ownedByUser($this->shareUserId)) {
            $joinOrCreateGroupShootBtnUrl = self::CREATED_GROUPSHOOT_BTN_URL;
        }
        else {
            $joinOrCreateGroupShootBtnUrl = $hadShareUserJoinedGroupShoot ? self::JOIN_GROUPSHOOT_BTN_URL : self::CREATED_GROUPSHOOT_BTN_URL;
        }

        $this->imageUrl .= '/image/' . base64_urlSafeEncode($joinOrCreateGroupShootBtnUrl) . '/gravity/SouthWest/dx/200/dy/495/ws/0.07';
        return $this;
    }

    /**
     * @return $this
     */
    protected function appendGroupShootInfo()
    {
        $title          = base64_urlSafeEncode($this->groupShoot->title);
        $this->imageUrl .= '/text/' . $title . '/fill/' . $this->fontColor . '/font/' . $this->chineseFont . '/fontsize/760/gravity/SouthWest/dx/95/dy/400';

        $moneyGift = $this->groupShoot->sharedMoneyGift();
        if ($moneyGift instanceof MoneyGift) {
            $receiveInfo    = base64_urlSafeEncode($moneyGift->receivedCount() . '人已领红包');
            $this->imageUrl .= '/text/' . $receiveInfo . '/fill/' . $this->fontColor . '/font/' . $this->chineseFont . '/fontsize/500/gravity/SouthWest/dx/125/dy/330/dissolve/80';

            $redbagImageUrl = base64_urlSafeEncode('http://v0.toseeapp.com/red-bag-v2.png');
            $this->imageUrl .= '/image/' . $redbagImageUrl . '/gravity/SouthWest/dx/95/dy/335/';
        }
        return $this;
    }

}

