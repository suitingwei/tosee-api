<?php

namespace App\Http\Controllers\V1;

use App\Models\GroupShootTemplate;
use App\Http\Controllers\Controller;
use App\Models\GroupShoot;
use App\Models\GroupShootRule;
use App\Models\MoneyGift;
use App\Models\User;
use App\Repositories\GroupShootRepository;
use App\Services\Helper;
use Illuminate\Http\Request;

/**
 * Class GroupShootController
 * @package App\Http\Controllers\V1
 */
class GroupShootController extends Controller
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var GroupShootRepository
     */
    private $groupShootRepository;

    public function __construct(Request $request, GroupShootRepository $groupShootRepository)
    {
        $this->request              = $request;
        $this->groupShootRepository = $groupShootRepository;
    }

    /**
     * Join the group shoot.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(Request $request)
    {
        $code       = $request->input('code', null);
        $groupShoot = GroupShoot::where('verify_code', $code)->first();

        if (!$groupShoot) {
            return Helper::response(['message' => 'Invalid code'], 1006);
        }

        if ($groupShoot->isDeleted()) {
            return Helper::response(['message' => 'The group shoot have been deleted!'], 1006);
        }

        return Helper::response(['id' => $groupShoot->id,'owner_id'=>$groupShoot->owner_id,'template_id'=>$groupShoot->template_id]);
    }

    /**
     * Create a new groupshoot,whether parent or child.
     * @return \Illuminate\Http\JsonResponse
     */
    public function store()
    {
        $this->validate($this->request,
            array_merge(GroupShoot::$storeRules, GroupShootRule::$storeRules)
        );

        return $this->groupShootRepository->createNewShoot();
    }

    /**
     * Get a parent groupshoot's detail info.
     * @param   $parentShootId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($parentShootId)
    {
        return $this->groupShootRepository->findById($parentShootId);
    }

    /**
     * Get the rules of this group shoot.
     * @param $groupShootId
     * @return \Illuminate\Http\JsonResponse
     */
    public function rules($groupShootId)
    {
        return $this->groupShootRepository->getRulesById($groupShootId);
    }

    /**
     * @param Request $request
     * @param         $groupShootId
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(Request $request, $groupShootId)
    {
        $startId  = $request->get('start_id', 0);
        $limit    = 21;
        $data     = [];
        $response = [];

        $groupShoot = new GroupShoot();
        if ($startId == 0) {
            $limit -= 1;
            if ($ownerGroupShoot = GroupShoot::where('id', $groupShootId)->first()) {
                $data[] = [
                    'id'             => $ownerGroupShoot->id,
                    'video_url'      => $ownerGroupShoot->video_url,
                    'webp_cover_url' => $ownerGroupShoot->webp_cover_url,
                    'gif_cover_url'  => $ownerGroupShoot->gif_cover_url,
                ];

                $ownerId                 = $ownerGroupShoot->owner_id;
                $ownerInfo               = User::find($ownerId);
                $response['owner']       = $ownerInfo;
                $response['verify_code'] = $ownerGroupShoot->verify_code;
            }
        }

        $groupShoot = $groupShoot->where('parent_id', $groupShootId);

        if ($startId > 0) {
            $groupShoot = $groupShoot->where('id', '<', $startId);
        }

        $groupShoots = $groupShoot->where('status', 1)->orderBy('id', 'desc')->take($limit)->get();
        foreach ($groupShoots as $item) {
            $money = 0;
            if ($moneyGiftData = MoneyGift::where('group_shoot_id', $item->id)->first()) {
                $money = $moneyGiftData->money;
            }
            $data[] = [
                'id'             => $item->id,
                'video_url'      => $item->video_url,
                'webp_cover_url' => $item->webp_cover_url,
                'gif_cover_url'  => $item->gif_cover_url,
                'money_gift'     => $money,
            ];
        }

        $response['group_shoots'] = $data;
        return Helper::response($response);
    }

    /**
     * If the user  is the owner of the parent groupshoot, he can delete any shoot of the group.
     * If not,fuck it off.
     * If the user is deleting the parent groupshoot, delete all of its children group shoots.
     * If the deleting groupshoot is the children groupshoot, delete itself.
     * @param Request $request
     * @param         $groupShootId
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request, $groupShootId)
    {
        $currentUserId = $request->input('user_id');
        $groupShoot    = GroupShoot::find($groupShootId);

        if ($groupShoot->isDeleted()) {
            return Helper::response(['message' => 'Group shoot have already been deleted!'], 1004);
        }

        //Only the owner of the parent groupshoot can delete group shoots.
        $parentGroupShoot = $groupShoot->isParentShoot() ? $groupShoot : $groupShoot->parent;

        if (!$parentGroupShoot->ownedByUser($currentUserId)) {
            return Helper::response(['message' => 'Unauthorization delete operation!'], 1004);
        }

        $groupShoot->deleteWithChildGroupShoots();

        return Helper::response();
    }

    /**
     * Get either user created group shoots or joined group shoots.
     * @return \Illuminate\Http\JsonResponse
     */
    public function owner()
    {
        switch ($this->request->input('s')) {
            case 'create':
                return $this->groupShootRepository->createdGroupShoots();
            case 'join':
                return $this->groupShootRepository->joinedGroupShoots();
            default:
                return Helper::response([], 400);
        }
    }

    /**
     * Get all members in this group shoot.
     * @param $groupShootId
     * @return \Illuminate\Http\JsonResponse
     */
    public function members($groupShootId)
    {
        $groupShoot = GroupShoot::find($groupShootId);

        $members = $groupShoot->joinedUsers(true, null, true)
                              ->map(function (User $user) use ($groupShoot) {
                                  $originalData         = $user->toArray();
                                  $takenMoneyGift       = $user->takenMoneyGiftFromGroupShoot($groupShoot);
                                  $firstChildGroupShoot = $groupShoot->childGroupShoots()->createdBy($user->id)->orderBy('created_at', 'asc')->first();
                                  if (!$firstChildGroupShoot && $groupShoot->ownedByUser($user->id)) {
                                      $firstChildGroupShoot = $groupShoot;
                                  }
                                  return array_merge($originalData, [
                                      'money_gift'  => $takenMoneyGift ? $takenMoneyGift->money : 0,
                                      'is_luckiest' => $firstChildGroupShoot->is_luckiest,
                                      'created_at'  => $firstChildGroupShoot->created_at->toDateTimeString(),
                                  ]);
                              })->sortBy('money_gift', SORT_NUMERIC, true)->values()->all();

        return Helper::response(['members' => $members]);
    }

    /**
     * Update the groupshoot info.
     * @param         $groupShootId
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($groupShootId, Request $request)
    {
        $groupShoot = GroupShoot::find($groupShootId);
        $userId     = $request->input('user_id');

        // Only the owner of the parent shoot can modify the parent groupshoot info.
        if (!$groupShoot->isParentShoot() || !$groupShoot->ownedByUser($userId)) {
            return Helper::response();
        }

        $groupShoot->update(['merge_shoots_title' => $request->input('merge_shoots_title')]);

        return Helper::response(['group_shoot' => $groupShoot]);
    }

    /**
     * Get the groupshoot all avaiable templates.
     */
    public function avaibleTemplates()
    {
        $templates = GroupShootTemplate::orderBy('sort', 'desc')->get();

        return Helper::response(['templates' => $templates]);
    }
}
