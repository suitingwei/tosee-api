<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use App\Services\Helper;
use Illuminate\Http\Request;

class ResourcesController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $resources = Resource::where('type', $request->input('type'))->get();

        return Helper::response(['resources' => $resources]);
    }
}
