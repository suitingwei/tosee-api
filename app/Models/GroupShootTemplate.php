<?php

namespace App\Models;

use App\Models\GroupShootTraits\SortOperation;
use Illuminate\Database\Eloquent\Model;

class GroupShootTemplate extends Model
{
    use SortOperation;
    public $guarded = [];
}
