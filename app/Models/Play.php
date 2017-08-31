<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Play
 *
 * @property int $id
 * @property int $owner_id
 * @property int $topic_id
 * @property string $video_key
 * @property float $time_frame
 * @property bool $choose
 * @property string $title
 * @property string $answer
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $music_key
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Play whereAnswer($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Play whereChoose($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Play whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Play whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Play whereMusicKey($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Play whereOwnerId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Play whereTimeFrame($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Play whereTitle($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Play whereTopicId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Play whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Play whereVideoKey($value)
 * @mixin \Eloquent
 */
class Play extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'plays';
}
