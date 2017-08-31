<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Helper;
use App\Services\UtilService;

class UtilController extends Controller
{
	public function weather(Request $request)
	{
		$ip = $request->ip();
		$weather = UtilService::weather($ip);
        return Helper::response($weather);
	}		
}
