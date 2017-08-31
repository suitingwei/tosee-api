<?php

namespace App\Services\Strategies;

use App\Services\QiniuService;

class GroupShootThumbNailShareV1 extends GroupShootThunmNailShare
{
    protected function setBackgroundImageUrl()
    {
        $this->imageUrl = QiniuService::getHttpsVideoUrl('share-background.png');
        return $this;
    }

}
