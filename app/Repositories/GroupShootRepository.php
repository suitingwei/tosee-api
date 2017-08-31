<?php

namespace App\Repositories;

use App\Formatter\GroupShootFormatter;
use App\Models\GroupShoot;
use App\Models\GroupShootRule;
use App\Models\User;
use App\Services\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class GroupShootRepository
{
    const SHOW_GROUPSHOOTS_PER_PAGE = 21;

    private $request = null;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Create a new groupshoot,includes parent shoot,and children shoot.
     * @return mixed
     */
    public function createNewShoot()
    {
        $createData = $this->buildNewGroupShootData();

        return $this->request->input('parent_id') == 0 ? $this->createParentGroupShoot($createData) : $this->createChildGroupShoot($createData);
    }

    /**
     * Create parent group shoot's rules.
     * @param GroupShoot $groupShoot
     */
    private function createParentGroupShootRules(GroupShoot $groupShoot)
    {
        $groupShoot->rule()->create([
            'theme'                => $this->request->input('theme', ''),
            'time'                 => $this->request->input('time', GroupShootRule::TIME_CONFIG_TEN_SECONDS),
            'canvas_direction'     => $this->request->input('canvas_direction', GroupShootRule::CANVAS_DIRECTION_HORIZONTAL),
            'camera_direction'     => $this->request->input('camera_direction', GroupShootRule::CAMERA_DIRECTION_BEHIND),
            'enable_red_bag'       => $this->request->input('enable_red_bag', GroupShootRule::ENABLE_RED_BAG),
            'enable_camera_filter' => $this->request->input('enable_camera_filter', GroupShootRule::ENABLE_CAMERA_FILTER),
            'enable_music'         => $this->request->input('enable_music', GroupShootRule::ENABLE_MUSIC),
            'enable_sticker'       => $this->request->input('enable_sticker', GroupShootRule::ENABLE_STICKER),
            'template_id'          => $this->request->input('template_id', null),
        ]);
    }


    /**
     * @param $createData
     * @return mixed
     * @throws \Exception
     */
    private function createChildGroupShoot($createData)
    {
        if (!GroupShoot::where('id', $this->request->input('parent_id'))->where('parent_id', 0)->first()) {
            return Helper::responseInvalidParameters();
        }

        if ($createData['type'] == GroupShoot::TYPE_MERGED) {
            $createData['merge_status'] = 1;
        }

        $createData['parent_id'] = $this->request->input('parent_id');

        $groupShoot = GroupShoot::create($createData);

        if ($groupShoot->isMergedShoot()) {
            return Helper::response(['id' => $groupShoot->id]);
        }

        if (!($parentShootMoneyGift = $groupShoot->parent->sharedMoneyGift())) {
            return Helper::response(['id' => $groupShoot->id, 'money_gift' => 0]);
        }

        return Helper::response([
            'id'         => $groupShoot->id,
            'money_gift' => $parentShootMoneyGift->sendMoneyToUser($groupShoot)
        ]);
    }

    /**
     * @param array $createData
     * @return \Illuminate\Http\JsonResponse
     */
    private function createParentGroupShoot(array $createData)
    {
        $this->createParentGroupShootRules($groupShoot = GroupShoot::create($createData));

        return Helper::response(['id' => $groupShoot->id]);
    }

    /**
     * @return array
     */
    private function buildNewGroupShootData()
    {
        return [
            'owner_id'           => $this->request->input('user_id'),
            'video_key'          => $this->request->input('video_key'),
            'original_video_key' => $this->request->input('original_video_key', ''),
            'music_key'          => $this->request->get('music_key', ''),
            'type'               => $this->request->get('type', GroupShoot::TYPE_NOT_MERGED),
            'status'             => GroupShoot::STATUS_NOT_DELETED,
            'title'              => $this->request->get('title', ''),
            'gif_cover_url'      => $this->request->input('gif_cover_url', ''),
            'sample_frame_keys'  => $this->request->input('sample_frame_keys', ''),
            'big_iframe_url'     => $this->request->input('big_iframe_url', ''),
        ];
    }

    /**
     * All users created group shoots.
     * @return \Illuminate\Http\JsonResponse
     */
    public function createdGroupShoots()
    {
        $response    = [];
        $page        = $this->request->get('start_id', 0);
        $user        = User::find($userId = $this->request->input('user_id'));
        $groupShoots = GroupShoot::where('parent_id', 0);

        if ($page > 0) {
            $groupShoots = $groupShoots->where('id', '<', $page);
        }

        $groupShoots = $groupShoots->createdBy($userId)
                                   ->notMerged()
                                   ->notDeleted()
                                   ->orderBy('id', 'desc')
                                   ->take(20)
                                   ->get();

        $response['group_shoots'] = $groupShoots->map(function (GroupShoot $groupShoot) {
            return $this->formatGroupShoot($groupShoot);
        });

        $response['createCount']      = $user->raisedGroupShootsCount();
        $response['createTotalCount'] = $user->raisedGroupShootsUserCount();

        return Helper::response($response);
    }

    /**
     * All user joined group shoots.
     * @return \Illuminate\Http\JsonResponse
     */
    public function joinedGroupShoots()
    {
        $response = [];
        $page     = $this->request->get('start_id', 0);
        $user     = User::find($userId = $this->request->input('user_id'));

        //获取用户的群拍的所有父群拍
        $joinedParentShootIds = GroupShoot::notMerged()
                                          ->createdBy($userId)
                                          ->notDeleted()
                                          ->notParentShoot()
                                          ->selectRaw('distinct parent_id')
                                          ->pluck('parent_id');

        //所有父群拍里不是该用户拍摄的
        $notDeletedParentShootIds = GroupShoot::whereIn('id', $joinedParentShootIds->all())
                                              ->notDeleted()
                                              ->notMerged()
                                              ->where('owner_id', '!=', $userId)
                                              ->pluck('id')
                                              ->all();

        $joinedGroupShoots = GroupShoot::createdBy($userId)->whereIn('parent_id', $notDeletedParentShootIds);

        if ($page > 0) {
            $joinedGroupShoots = $joinedGroupShoots->where('id', '<', $page);
        }

        $joinedGroupShoots = $joinedGroupShoots->orderBy('id', 'desc')->groupBy('parent_id')->take(20)->get();

        $response['group_shoots'] = $joinedGroupShoots->map(function (GroupShoot $groupShoot) {
            return $this->formatGroupShoot($groupShoot);
        });

        $response['joinCount']      = $user->joinedGroupShootCount();
        $response['joinTotalCount'] = $user->joinedGroupShootsUserCount();

        return Helper::response($response);
    }

    /**
     * @param GroupShoot $groupShoot
     * @return array
     * @internal param $source
     */
    private function formatGroupShoot(GroupShoot $groupShoot)
    {
        return [
            'id'             => $groupShoot->id,
            'parent_id'      => $groupShoot->parent_id,
            'video_url'      => $groupShoot->video_url,
            'webp_cover_url' => $groupShoot->webp_cover_url,
            'gif_cover_url'  => $groupShoot->gif_cover_url,
            'money_gift'     => $groupShoot->moneyGifts->count() > 0 ? $groupShoot->moneyGifts->first()->money : 0,
            'join_count'     => $groupShoot->parent ? $groupShoot->parent->joinedUsersCount() : $groupShoot->joinedUsersCount(),
            'template_id'    => $groupShoot->template_id,
            'title'          => $groupShoot->title,
        ];

    }

    /**
     * Get a parent shoot's detail info.
     * 1.The merged group shoots.
     * 2.The children not merged group shoots.
     * @param $parentShootId
     * @return \Illuminate\Http\JsonResponse
     */
    public function findById($parentShootId)
    {
        if (!($parentShoot = GroupShoot::where('id', $parentShootId)->where('status', 1)->first())) {
            return Helper::response([], 404);
        }
        $returnData = array_merge([
            'merge_group_shoots' => $this->getMergedChildrenShoots($parentShoot),
            'group_shoots'       => $this->getNotMergedChildrenShoots($parentShoot),
        ], call_user_func(GroupShootFormatter::getShowFormatterWithBriefInfo(), $parentShoot));

        return Helper::response($returnData);
    }

    /**
     * @param GroupShoot $parentGroupShoot
     * @return \Illuminate\Http\JsonResponse
     * @internal param $parentShootId
     */
    public function findByIdForMobile(GroupShoot $parentGroupShoot)
    {
        $resultData = array_merge([
            'had_visited'  => false,
            'group_shoots' => $this->getAllGroupShootsPaginated($parentGroupShoot),
        ], call_user_func(GroupShootFormatter::getShowFormatterWithBriefInfo(), $parentGroupShoot));

        //This browser had never saw this page.
        if (!$this->request->session()->has('had_visited')) {
            $this->request->session()->put('had_visited', 0);
            return $resultData;
        }

        //If the browser had visited the site, we should judge the whether the browser had visited this specific page.
        if ($this->request->session()->has('had_visited')) {
            $this->request->session()->put('had_visited', 1);
            $cacheKey = $this->request->cookie('laravel_session') . $this->request->fullUrl();
            if (!Redis::exists($cacheKey)) {
                Redis::set($cacheKey, 'visited');
                return $resultData;
            }
        }

        $resultData['had_visited'] = true;
        return $resultData;
    }

    /**
     * @param GroupShoot $parentShoot
     * @return array
     * @internal param $parentShootId
     */
    private function getMergedChildrenShoots(GroupShoot $parentShoot)
    {
        return $parentShoot->childGroupShoots()
                           ->merged()
                           ->orderBy('id', 'desc')
                           ->take(1)
                           ->get()
                           ->map(GroupShootFormatter::getShowFormatterForChildShoot());
    }

    /**
     * Get not merged child group shoots.
     * 1.Attention!! The parent shoot itself must be the first one of the group shoots.
     * @param GroupShoot $parentShoot
     * @return array
     */
    private function getNotMergedChildrenShoots(GroupShoot $parentShoot)
    {
        $page             = $this->request->input('start_id', 0);
        $largesTakenMoney = $parentShoot->taken_largest_money;
        $childGroupShoots = $parentShoot->childGroupShoots()
                                        ->notMerged()
                                        ->notDeleted()
                                        ->orderBy('id', 'asc')
                                        ->take($page == 0 ? self::SHOW_GROUPSHOOTS_PER_PAGE - 1 : self::SHOW_GROUPSHOOTS_PER_PAGE);
        if ($page > 0) {
            $childGroupShoots = $childGroupShoots->where('id', '<', $page);
        }

        $groupShoots = $childGroupShoots->get()->map(GroupShootFormatter::getShowFormatterForChildShoot($largesTakenMoney));

        //The parent shoot should be in the first place of the first page.
        if ($page == 0) {
            $groupShoots->prepend(call_user_func(GroupShootFormatter::getShowFormatterForChildShoot($largesTakenMoney), $parentShoot));
        }

        return $groupShoots;
    }

    /**
     * In order to speed up the thumb nail share process, we'll return the thunmb nail image url in the rules api,
     * because the client will call this api whenever the user get in the groupshoot detail page, so it can download the
     * share thunmbnail image sliently.
     * Rememeber, only the parent shoot has the rules.
     * @param $groupShootId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRulesById($groupShootId)
    {
        if (!($parentShoot = GroupShoot::where('id', $groupShootId)->notDeleted()->parentShoot()->first())) {
            return Helper::response([], 404);
        }

        $thumbnailShareUrl = $parentShoot->getThumbnailShareUrlAttribute($this->request->input('user_id'));

        return Helper::response(['rules' => $parentShoot->rules, 'thumbnail_share_url' => $thumbnailShareUrl]);
    }

    public function getAllGroupShootsPaginated(GroupShoot $parentShoot)
    {
        $page             = $this->request->input('start_id', 0);
        $largesTakenMoney = $parentShoot->taken_largest_money;

        //If there is only the parent group shoot, return it.
        if (!$parentShoot->hadChildrenShoots()) {
            return [call_user_func(GroupShootFormatter::getShowFormatterForChildShoot($largesTakenMoney), $parentShoot)];
        }

        //If there is any other merged shoots, return them and filter the parent group shoot.
        $childGroupShoots = $parentShoot->childGroupShoots()
                                        ->notMerged()
                                        ->notDeleted()
                                        ->orderBy('id', 'desc')
                                        ->take(21);
        if ($page > 0) {
            $childGroupShoots = $childGroupShoots->where('id', '<', $page);
        }

        return $childGroupShoots->get()->map(GroupShootFormatter::getShowFormatterForChildShoot($largesTakenMoney));
    }
}

