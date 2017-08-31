<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\GroupShoot;
use App\Services\Helper;
use App\Services\ImageService;
use App\Services\QiniuService;
use App\Services\WechatService;
use Illuminate\Http\Request;
use Qiniu;

/**
 */
class ShareController extends Controller
{
    /**
     * @param Request $request
     * @param         $groupShootId
     *
     * @return \Illuminate\Http\JsonResponse`
     */
    public function getGroupShootShareInfo(Request $request, $groupShootId)
    {
        $groupShoot = GroupShoot::where('id', $groupShootId)->notDeleted()->first();

        if (!$groupShoot) {
            return Helper::response(['message' => 'group shoot not found'], 1004);
        }

        //Notice we will only share the parent groupshoot info, if the user's input is the //children groupshoot,find its parent.
        if (!$groupShoot->isParentShoot()) {
            $groupShoot = $groupShoot->parent;
        }


        $shareInfo = $groupShoot->getThumbnailShareInfoAttribute($request->input('user_id'));
        return Helper::response([
            'share_text'               => $groupShoot->share_text,
            'share_title'              => $groupShoot->getTextShareTitleAttribute($request->input('user_id')),
            'text_share_url'           => $groupShoot->mobile_url,
            'thumbnail_share_url'      => $groupShoot->getThumbnailShareUrlAttribute($request->input('user_id')),
            'thumbnail_share_title'    => $groupShoot->thumbnail_share_title,
            'text_share_thumbnail_url' => $groupShoot->text_share_thumbnail_url,
            'template_thumbnail_url'=> $groupShoot->getTemplateThumbnailUrlAttribute($request->input('user_id')),
            'shootImages'              => $shareInfo['covers'],
            'nickName'                 => $shareInfo['user_name'],
            'avatarUrl'                => $shareInfo['avatar'],
            'hasRedPacket'             => $shareInfo['had_red_bags'],
            'groupTitle'               => $shareInfo['title'],
            'showStart'                => (boolean)$shareInfo['joinOrCreate'],
            'tokenCount'               => $shareInfo['taken_money_users_count'],
        ]);
    }

    /**
     * Get merged group shoots cover.
     * ---------------------------------------------------------------
     * 1.围观分享 就取最新拍摄的视频 取4帧
     * 2.参与分享 取最近拍摄的视频 取4帧
     * 3.发起分享 取发起视频 4帧
     *
     * @param         $groupShootId
     *
     * @param Request $request
     *
     * @internal param Request $request
     */
    public function getMergeGroupShootCovers($groupShootId, Request $request)
    {
        $groupShoot       = null;
        $parentGroupShoot = GroupShoot::find($groupShootId);
        $userId           = $request->input('user_id');

        $childGroupShoot = $parentGroupShoot->childGroupShoots()->notMerged()->notDeleted()->createdBy($userId)->orderBy('id', 'desc')->first();
        //If the user had joined the groupshoots, then use his last group shoot.
        if ($childGroupShoot) {
//            $imagesArray = QiniuService::getVideoSampleFrames($childGroupShoot->original_video_key);
            //$imagesArray =array_fill(0, 4, $childGroupShoot->gif_cover_url);
            ImageService::generateMeredImage($childGroupShoot->sample_frame_urls);
        }

        //If the user had not joined the groupshoots,we'll chooose different share policy depends on the children group shoots.
        $childrenGroupShoots = $parentGroupShoot->childGroupShoots()->notMerged()->notDeleted()->orderBy('id', 'desc')->get();

        if ($childrenGroupShoots->count() < 3) {
            //Otherwise,use the owner's last group shoot.
            $groupShoot = $parentGroupShoot->childGroupShoots()->createdBy($parentGroupShoot->owner_id)->notMerged()->notDeleted()->orderBy('id', 'desc')->first();

            //Incase the group shoot has no children group shoots,we should share the parent shoot.
            $groupShoot = $groupShoot ?: $parentGroupShoot;
            //$imagesArray = QiniuService::getVideoSampleFrames($groupShoot->original_video_key);
            //$imagesArray = array_fill(0, 4, $groupShoot->gif_cover_url);
            $imagesArray = $groupShoot->sample_frame_urls;
        }
        else {
            $imagesArray = [];
            //If the children groupshoots have more than 3 counts,we'll choose 4 different groupshoots' cover to make the merged-cover.
            foreach ($childrenGroupShoots as $groupShoot) {
                array_push($imagesArray, $groupShoot->square_cover_url);
            }
            if (count($imagesArray) < 4) {
                array_push($imagesArray, $parentGroupShoot->square_cover_url);
            }
        }
        ImageService::generateMeredImage($imagesArray);
    }

    /**
     * Get share cover image,with merged-covers and owner info, groupshoot info.
     * ---------------------------------------------------------------
     * 1.围观分享 就取最新拍摄的视频 取4帧
     * 2.参与分享 取最近拍摄的视频 取4帧
     * 3.发起分享 取发起视频 4帧
     *
     * @param         $groupShootId
     * @param Request $request
     */
    public function getShareCover($groupShootId, Request $request)
    {
        $groupShoot       = null;
        $parentGroupShoot = GroupShoot::find($groupShootId);
        $userId           = $request->input('user_id');

        $childGroupShoot = $parentGroupShoot->childGroupShoots()->notMerged()->notDeleted()->createdBy($userId)->orderBy('id', 'desc')->first();
        //If the user had joined the groupshoots, then use his last group shoot.
        if ($childGroupShoot) {
            ImageService::generateGroupShootShareImage(QiniuService::getVideoSampleFrames($childGroupShoot->original_video_key), $parentGroupShoot, $userId);
        }

        //If the user had not joined the groupshoots,we'll chooose different share policy depends on the children group shoots.
        $childrenGroupShoots = $parentGroupShoot->childGroupShoots()->notMerged()->notDeleted()->orderBy('id', 'desc')->get();

        if ($childrenGroupShoots->count() < 3) {
            //Otherwise,use the owner's last group shoot.
            $groupShoot = $parentGroupShoot->childGroupShoots()->createdBy($parentGroupShoot->owner_id)->notMerged()->notDeleted()->orderBy('id', 'desc')->first();

            //Incase the group shoot has no children group shoots,we should share the parent shoot.
            $groupShoot  = $groupShoot ?: $parentGroupShoot;
            $imagesArray = QiniuService::getVideoSampleFrames($groupShoot->original_video_key);
        }
        else {
            $imagesArray = [];
            //If the children groupshoots have more than 3 counts,we'll choose 4 different groupshoots' cover to make the merged-cover.
            foreach ($childrenGroupShoots as $groupShoot) {
                array_push($imagesArray, $groupShoot->square_cover_url);
            }
            if (count($imagesArray) < 4) {
                array_push($imagesArray, $parentGroupShoot->square_cover_url);
            }
        }
        ImageService::generateGroupShootShareImage($imagesArray, $parentGroupShoot, $userId);
    }

    /**
     * Generate the watermark for the sharing groupshoot.
     *
     * @param $text
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function watermark($text)
    {
        if (!$text = Qiniu\base64_urlSafeDecode($text)) {
            return response()->json(['error' => 'input invalid']);
        }

        ImageService::generateWatermarkTextImage($text);
    }

    /**
     * Generate the qrcode for the sharing groupshoot.
     *
     * @param $url
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function qrcode($url)
    {
        $image = ImageService::generateQrCode($url);

        return response($image, 200, ['Content-Type' => 'image/png']);
    }

    public function avatarToCircle(Request $request)
    {
        header("Content-type: image/png");
        $avatarUrl = Qiniu\base64_urlSafeDecode($request->input('avatar'));
        $avatarUrl = str_replace('http://', 'https://', $avatarUrl);
        $image     = ImageService::cropIntoCircle($avatarUrl);
        imagepng($image);
        exit();
    }

    public function getWechatShareConfig(Request $request)
    {
        $currentUrl = $request->input('current_url');

        $signPackage = WechatService::getWechatJsApiSignPackage($currentUrl);

        return Helper::response(['sign' => $signPackage]);
    }
}

