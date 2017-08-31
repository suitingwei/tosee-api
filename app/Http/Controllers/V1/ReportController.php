<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\GroupShoot;
use App\Models\Report;
use App\Models\User;
use App\Services\Helper;
use Illuminate\Http\Request;


class ReportController extends Controller
{
    public function store(Request $request)
    {
        $user = User::find($request->input('user_id'));
        $id   = $request->get('id');

        if (GroupShoot::where('id', $id)->count() <= 0) {
            return Helper::response(['message' => 'GroupShoot not exist'], 404);
        }

        if (Report::where('user_id', $user->id)->where('value', $id)->count() > 0) {
            return Helper::response([]);
        }

        Report::create([
            'user_id' => $user->id,
            'value'   => $id,
        ]);

        return Helper::response([]);
    }

}
