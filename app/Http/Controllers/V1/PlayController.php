<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Play;
use App\Services\Helper;
use App\Services\QiniuService;
use Illuminate\Http\Request;
use Log;

class PlayController extends Controller
{
    public function store(Request $request)
    {
        $rule  = [
            'topic_id'  => 'integer',
            'choose'    => 'required',
            'time'      => 'required',
            'video_key' => 'required',
            'title'     => 'required',
            'answers'   => 'required|json',
        ];
        $input = $request->only(array_keys($rule));

        return $this->validator($rule, $input, function () use ($request, $input) {

            $play             = new Play();
            $play->owner_id   = $request->uid;
            $play->video_key  = $input['video_key'];
            $play->time_frame = $input['time'];
            $play->choose     = $input['choose'];
            $play->title      = $input['title'];
            $play->answer     = $input['answers'];
            $play->topic_id   = $input['topic_id'];
            if ($play->save()) {

                $mobileHost            = env('MOBILE_HOST');
                $textShareUrl          = Helper::url('mp/share/play/' . $play->id, $mobileHost);
                $thumbnailShareUrl     = QiniuService::getThumbnailShareUrl($play->video_key, $textShareUrl, $play->title);
                $textShareThumbnailUrl = QiniuService::getTextShareThumbnailUrl($play->video_key);

                $response = [
                    'play_id'                  => $play->id,
                    'text_share_url'           => $textShareUrl,
                    'thumbnail_share_url'      => $thumbnailShareUrl,
                    'text_share_thumbnail_url' => $textShareThumbnailUrl,
                    'share_title'              => $play->title,
                    'share_text'               => "点击链接查看答案，你猜对了吗？",
                ];

                $notifyUrl = Helper::url('notify/qiniu/pfop', env('API_HOST'));
                QiniuService::makeVideoWatermark($play->video_key, $notifyUrl);
                QiniuService::makeShareThumbnail($play->video_key);

                Log::info("[play response] " . json_encode($response, JSON_UNESCAPED_UNICODE));

                return Helper::response($response);
            }

            return Helper::response(['message' => 'add play failed'], 5001);
        });
    }

    public function update(Request $request, $id)
    {
        $rule  = [
            'topic_id' => 'integer',
            'choose'   => 'required',
            'time'     => 'required',
            'title'    => 'required',
            'answers'  => 'required|json',
        ];
        $input = $request->only(array_keys($rule));

        return $this->validator($rule, $input, function () use ($request, $input, $id) {

            if (!$play = Play::find($id)) {
                return Helper::response(['message' => 'play not found'], 4004);
            }

            $play->time_frame = $input['time'];
            $play->choose     = $input['choose'];
            $play->title      = $input['title'];
            $play->answer     = $input['answers'];
            $play->topic_id   = $input['topic_id'];
            if ($play->save()) {

                $mobileHost            = env('MOBILE_HOST');
                $textShareUrl          = Helper::url('mp/share/play/' . $play->id, $mobileHost);
                $thumbnailShareUrl     = QiniuService::getThumbnailShareUrl($play->video_key, $textShareUrl, $play->title);
                $textShareThumbnailUrl = QiniuService::getTextShareThumbnailUrl($play->video_key);

                $response = [
                    'play_id'                  => $play->id,
                    'text_share_url'           => $textShareUrl,
                    'thumbnail_share_url'      => $thumbnailShareUrl,
                    'text_share_thumbnail_url' => $textShareThumbnailUrl,
                    'share_title'              => $play->title,
                    'share_text'               => "点击链接查看答案，你猜对了吗？",
                ];

                Log::info("[play response] " . json_encode($response, JSON_UNESCAPED_UNICODE));
                return Helper::response($response);
            }

            return Helper::response(['message' => 'update play failed'], 5001);
        });
    }

    /**
     * @param Request $request
     * @param         $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        if (!$play = Play::find($id)) {
            return Helper::response(['message' => 'play not found'], 4004);
        }

        return Helper::response([
            'play_id'    => $play->id,
            'share_text' => $play->title,
        ]);
    }
}
