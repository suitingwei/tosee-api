<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;
use App\Services\Helper;
use App\Models\Topic;

class TopicController extends Controller
{
    public function index()
    {
        $topics = json_decode(Redis::hget('topics', 'default'));
        $qiniuUrl = 'http://'.env('QINIU_APP_DOMAIN').'/';

        $data[] = [
            'id' => 0,
            'title' => '',
            'abbr' => '新年快乐',
            'content' => '用你家乡的方言给大家拜年，或是关于春节的故事，再让大家猜你是那里人。祝大家春节快乐！',
            'cover' => $qiniuUrl.'topic/icon/0@2x.png',
            'answers' => '',
            'type' => 2
        ];

        foreach ($topics as $topic) {
            $data[] = [
                'id' => $topic->id,
                'title' => $topic->title,
                'abbr' => $topic->abbr,
                'content' => $topic->content,
                'cover' => $qiniuUrl.$topic->cover,
                'answers' => json_decode($topic->answer),
                'type' => 1
            ];
        }

        return Helper::response($data);
    }

    public function groupshoot()
    {
        $topics = json_decode(Redis::hget('topics', 'groupshoot'));
        $topicRands = json_decode(Redis::hget('topics', 'groupshoot-rand'));
        $qiniuUrl = 'http://'.env('QINIU_APP_DOMAIN').'/';
        foreach ($topics as $topic) {
            $tmpTopics = array_merge($topicRands, array($topic->content));
            $data[] = [
                'id' => $topic->id,
                'title' => $topic->title,
                'abbr' => $topic->abbr,
                'content' => $tmpTopics[array_rand($tmpTopics)],
                'cover' => $qiniuUrl.$topic->cover,
                'background_music_file' => $topic->background_music_file,
                'gif_file' => $topic->gif_file,
            ];
        }

        return Helper::response($data);
    }

}
