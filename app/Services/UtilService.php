<?php

namespace App\Services;

use Log;
use GuzzleHttp\Client;
use Illuminate\Support\Str;

class UtilService 
{
	public static function weather($city)
	{
		
		$location = $city; // 除拼音外，还可以使用 v3 id、汉语等形式
		$key = "dgv5qm5yyp1mzm8c"; // 测试用 key，请更换成您自己的 Key
		$uid = "U08C2E164F"; // 测试用 用户ID，请更换成您自己的用户ID
		// 获取当前时间戳，并构造验证参数字符串
		$keyname = "ts=".time()."&ttl=30&uid=".$uid;
		// 使用 HMAC-SHA1 方式，以 API 密钥（key）对上一步生成的参数字符串（raw）进行加密
		$sig = base64_encode(hash_hmac('sha1', $keyname, $key, true));
		// 将上一步生成的加密结果用 base64 编码，并做一个 urlencode，得到签名sig
		$signedkeyname = $keyname."&sig=".urlencode($sig);
		// 最终构造出可由前端进行调用的 url
		$url = "https://api.thinkpage.cn/v3/weather/now.json?location=".$location."&".$signedkeyname;
		$data = file_get_contents($url);
		$obj_data = json_decode($data, true);

		$now = $obj_data['results'][0]['now'];

		$weather = [
			'weather' => $now['text'],	
			'temp' => $now['temperature'],
			'humidity' => $now['humidity'],
			'wind_speed' => $now['wind_speed'],
		];
		return $weather;
	}

}
