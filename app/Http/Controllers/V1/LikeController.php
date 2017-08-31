<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Helper;
use App\Services\UtilService;
use App\Models\Like;
use App\Models\User;
use App\Models\GroupShoot;

class LikeController extends Controller
{
	
	public function store(Request $request)
	{
		$user = User::find($request->input('user_id'));
		$id = $request->get('id');

		if (GroupShoot::where('id', $id)->count() <= 0) {
			return Helper::response(['message' => 'GroupShoot not exist'], 404);
		}

		if (Like::where('user_id', $user->id)->where('value', $id)->count() > 0) {
			return Helper::response([]);
		}

		Like::create([
			'user_id' => $user->id,
			'value' => $id,
		]);

		return Helper::response([]);
	}	

/**
 * sdnoansdoi 
 * @param  Request $request [description]
 * @return [type]           [description]
 */
	public function destroy(Request $request) 
	{
		$user = User::find($request->input('user_id'));
		$id = $request->get('id');

		if (GroupShoot::where('id', $id)->count() <= 0) {
			return Helper::response(['message' => 'GroupShoot not exist'], 404);
		}

		if (Like::where('user_id', $user->id)->where('value', $id)->count() <= 0) {
			return Helper::response(['message' => 'Dislike failure'], 500);
		}
		
		$like = Like::where('user_id', $user->id)->where('value', $id)->first();
		$like->delete();

		return Helper::response([]);
	}
}
