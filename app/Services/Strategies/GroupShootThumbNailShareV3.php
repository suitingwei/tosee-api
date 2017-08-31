<?php

namespace App\Services\Strategies;

use App\Services\Helper;
use Redis;
use function Qiniu\base64_urlSafeEncode;

class GroupShootThumbNailShareV3 extends GroupShootThunmNailShare
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
        $mergedCoversUrl      = $this->groupShoot->getShareGroupShootUrlAttribute($this->shareUserId);
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

    public function appendGroupShootInfo()
    {
        return $this;
    }

    public function appendOwnerInfo()
    {
        return $this;
    }
}
