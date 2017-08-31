<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Topic
 *
 * @property int $id
 * @property string $title
 * @property string $abbr
 * @property string $content
 * @property string $cover
 * @property string $answer
 * @property bool $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Topic whereAbbr($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Topic whereAnswer($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Topic whereContent($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Topic whereCover($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Topic whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Topic whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Topic whereStatus($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Topic whereTitle($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Topic whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Topic extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'topics';

	protected $guarded = [];
}
