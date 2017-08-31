<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Report
 *
 * @property int $id
 * @property int $user_id
 * @property int $value
 * @property string $type
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Report whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Report whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Report whereType($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Report whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Report whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Report whereValue($value)
 * @mixin \Eloquent
 */
class Report extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'reports';

	protected $guarded = [];
}
