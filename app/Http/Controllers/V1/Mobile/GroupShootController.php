<?php

namespace App\Http\Controllers\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Models\GroupShoot;
use App\Repositories\GroupShootRepository;
use App\Services\Helper;

class GroupShootController extends Controller
{
    /**
     * @var GroupShootRepository
     */
    private $groupShootRepository;

    public function __construct(GroupShootRepository $groupShootRepository)
    {
        $this->groupShootRepository = $groupShootRepository;
    }

    public function show($parentShootId)
    {
		\Log::info('mobile group shoot show method requested');
        if (!($parentShoot = GroupShoot::where('id', $parentShootId)->where('status', 1)->first())) {
            return Helper::response([], 404);
        }

        $result = $this->groupShootRepository->findByIdForMobile($parentShoot);

        return Helper::response($result);
    }
}
