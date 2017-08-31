<?php

namespace App\Models;

use App\Services\QiniuService;
use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed logo_key
 * @property mixed file_key
 */
class Resource extends Model
{
    const TYPE_TEMPLATE = 'TEMPLATE';
    const TYPE_MUSIC    = 'MUSIC';
    const TYPE_STICKER  = 'STICKER';

    public $guarded = [];
    public $appends = ['file_url', 'logo_url', 'landscape_url','template_water_url'];
    public $hidden = ['file_key', 'logo_key', 'sort', 'created_at', 'updated_at', 'introduction', 'title'];


    /**
     * @return string
     */
    public function getFileUrlAttribute()
    {
        return QiniuService::getHttpsVideoUrl($this->file_key);
    }

    /**
     * @return string
     */
    public function getLogoUrlAttribute()
    {
        return QiniuService::getHttpsVideoUrl($this->logo_key);
    }

    /**
     * Get the land scape sticker url.
     */
    public function getLandScapeUrlAttribute()
    {
        $landScapeKey = 'landscape_' . $this->attributes['file_key'];

        return QiniuService::getHttpsVideoUrl($landScapeKey);
    }

    public function getTemplateWaterUrlAttribute()
    {
        return QiniuService::getHttpsVideoUrl($this->attributes['template_water_key']);
    }
}
