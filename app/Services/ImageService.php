<?php

namespace App\Services;

use App\Models\GroupShoot;
use App\Models\GroupShootRule;
use App\Models\User;
use Endroid\QrCode\QrCode;
use function Qiniu\base64_urlSafeDecode;

class ImageService
{
    const JOIN_GROUPSHOOT_BTN_URL    = 'http://v0.toseeapp.com/joined-groupshoot-btn.png';
    const CREATED_GROUPSHOOT_BTN_URL = 'http://v0.toseeapp.com/created-groupshoot-btn.png';

    const START_X                      = 0;
    const START_Y                      = 0;
    const IMAGE_WIDTH                  = 158;
    const IMAGE_HEIGHT                 = 158;
    const MARGIN_WIDTH                 = 2;
    const MARGIN_HEIGHT                = 2;
    const GROUP_SHOOT_INFO_AREA_HEIGHT = 151;

    /**
     * Generate the groupshoot merged four images when sharing the groupshoot with * thumbnail.
     * @see https://app.zeplin.io/project/58c7ffdcff98945ac51ca72a/screen/58f72ffa2f0a014982be9eec
     *
     * @param array $imageUrls
     */
    public static function generateMeredImage(array $imageUrls)
    {
        header('Content-Type: image/jpeg');
        $startX       = 0;
        $startY       = 0;
        $imageWidth   = 158;
        $imageHeight  = 158;
        $marginWidth  = 2;
        $marginHeight = 2;
        $canvasWidth  = $imageWidth * 2 + $marginWidth;
        $canvasHeight = $imageWidth * 2 + $marginHeight;

        $canvas = imagecreatetruecolor($canvasWidth, $canvasHeight);
        $black  = imagecolorallocate($canvas, 0, 0, 0);
        imagefill($canvas, 0, 0, $black);

        $image = imagecreatefrompng($imageUrls[0]);
        imagecopymerge($canvas, $image, $startX, $startY, 0, 0, $imageWidth, $imageHeight, 100);

        $image = imagecreatefrompng($imageUrls[1]);
        imagecopymerge($canvas, $image, $startX + $imageWidth + $marginWidth, $startY, 0, 0, $imageWidth, $imageHeight, 100);

        $image = imagecreatefrompng($imageUrls[2]);
        imagecopymerge($canvas, $image, $startX, $startY + $imageHeight + $marginHeight, 0, 0, $imageWidth, $imageHeight, 100);

        $image = imagecreatefrompng($imageUrls[3]);
        imagecopymerge($canvas, $image, $startX + $imageWidth + $marginWidth, $startY + $imageHeight + $marginHeight, 0, 0, $imageWidth, $imageHeight, 100);

        imagejpeg($canvas);

        //Free the image memory.
        imagedestroy($canvas);
        imagedestroy($image);
        exit();
    }

    /**
     * Crop the image into specific shape.
     *
     * @param        $fileUrl
     *
     * @param string $direction
     *
     * @return bool|resource
     */
    public static function cropGroupShootCover($fileUrl, $direction = GroupShootRule::CANVAS_DIRECTION_VERTICAL)
    {
        $image = imagecreatefromjpeg($fileUrl);

        return imagescale($image, 330, 330);
    }

    /**
     * Generate the qrcode image for the specfic url.
     *
     * @param $url
     *
     * @return string
     */
    public static function generateQrCode($url)
    {
        return (new QrCode())->setText(base64_urlSafeDecode($url))
                             ->setSize(150)
                             ->setPadding(0)
                             ->setErrorCorrection('high')
                             ->setForegroundColor(array('r' => 0, 'g' => 0, 'b' => 0, 'a' => 0))
                             ->setBackgroundColor(array('r' => 255, 'g' => 255, 'b' => 255, 'a' => 0))
                             ->get('png');
    }

    /**
     * Generate the watermark text image.
     * We can not use the qiniu image api,because we need the watermark text with background color,
     * That's which qiniu not support.
     *
     * @param $text
     *
     * @return resource
     */
    public static function generateWatermarkTextImage($text)
    {
        header('Content-Type: image/png');

        $str                           = str_limit(self::removeEmoji($text), 120);
        $stringExplodedByLineSeparator = explode("\n", $str);
        $upHalfStr                     = $stringExplodedByLineSeparator[0];
        $downHalf                      = isset($stringExplodedByLineSeparator[1]) ? $stringExplodedByLineSeparator[1] : '';
        $upHalfLinesCount              = self::getLinesByStr($upHalfStr);
        $downHalfLinesCount            = self::getLinesByStr($downHalf);
        $lines                         = ($upHalfLinesCount + $downHalfLinesCount) <= 4 ? ($upHalfLinesCount + $downHalfLinesCount) : 4;
        $image                         = self::initWaterImageBase($lines);
        $white                         = imagecolorallocate($image, 255, 255, 255);
        $font                          = base_path() . '/app/PingFang-Bold.ttf';

        for ($i = 0; $i < $upHalfLinesCount; ++$i) {
            $start = $i * 13;
            $text  = mb_substr($upHalfStr, $start, 13);
            imagettftext($image, 35, 0, 42, 78 + ($i * 64), $white, $font, $text);
        }

        for ($downHalfIndex = 0; $downHalfIndex < $downHalfLinesCount; ++$downHalfIndex) {
            $start = $downHalfIndex * 13;
            $text  = mb_substr($downHalf, $start, 13);
            imagettftext($image, 35, 0, 42, 78 + (($i + $downHalfIndex) * 64), $white, $font, $text);
        }

        imagepng($image);

        //Free the image memory.
        imagedestroy($image);
    }

    private static function removeEmoji($text)
    {
        // Match Emoticons
        $regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
        $clean_text     = preg_replace($regexEmoticons, '', $text);

        // Match Miscellaneous Symbols and Pictographs
        $regexSymbols = '/[\x{1F300}-\x{1F5FF}]/u';
        $clean_text   = preg_replace($regexSymbols, '', $clean_text);

        // Match Transport And Map Symbols
        $regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
        $clean_text     = preg_replace($regexTransport, '', $clean_text);

        // Match Miscellaneous Symbols
        $regexMisc  = '/[\x{2600}-\x{26FF}]/u';
        $clean_text = preg_replace($regexMisc, '', $clean_text);

        // Match Dingbats
        $regexDingbats = '/[\x{2700}-\x{27BF}]/u';
        $clean_text    = preg_replace($regexDingbats, '', $clean_text);

        return $clean_text;
    }

    /**
     * @param $string
     *
     * @return int
     */
    private static function getLinesByStr($string)
    {
        return intval(ceil(mb_strlen($string) / 13));
    }

    /**
     * @param $lines
     *
     * @return resource
     */
    private static function initWaterImageBase($lines)
    {
        $height     = self::getWaterTextImageHeightByLines($lines);
        $image      = imagecreatetruecolor(720, $height);
        $background = imagecolorallocate($image, 237, 73, 86);
        imagefilledrectangle($image, 0, 0, 720, 300, $background);
        return $image;
    }

    /**
     * @param $lines
     *
     * @return int
     */
    private static function getWaterTextImageHeightByLines($lines)
    {
        switch ($lines) {
            case 1:
                return 125;
            case 2:
                return 185;
            case 3:
                return 265;
            case 4:
                return 295;
        }
    }

    /**
     * Crop the image into circle.
     *
     * @param $fileUrl
     *
     * @internal param $input
     * @return bool
     */
    public static function cropIntoCircle($fileUrl)
    {
        $width  = 100;
        $height = 100;

        $image = imagecreatefromjpeg($fileUrl);
        $image = imagescale($image, $width, $height);
        $mask  = imagecreatetruecolor($width, $height);
        $bg    = imagecolorallocate($mask, 255, 255, 255);
        imagefill($mask, 0, 0, $bg);

        $e = imagecolorallocate($mask, 0, 0, 0);
        $r = $width <= $height ? $width : $height;
        imagefilledellipse($mask, ($width / 2), ($height / 2), $r, $r, $e);
        imagecolortransparent($mask, $e);
        imagecopymerge($image, $mask, 0, 0, 0, 0, $width, $height, 100);
        imagecolortransparent($image, $bg);
        return $image;
    }

    /**
     * @param array $imageUrls
     * @param       $parentGroupShoot
     * @param       $userId
     *
     * @return bool
     * @internal param $imagesArray
     */
    public static function generateGroupShootShareImage(array $imageUrls, GroupShoot $parentGroupShoot, $userId)
    {
        header('Content-type : image/jpeg');

        $canvas = self::createCanvas();
        self::appendMergedCovers($canvas, $imageUrls);
        self::appendOwner($canvas, $parentGroupShoot, $userId);

//        $font  = base_path() . '/app/PingFang-Bold.ttf';
//
//            imagettftext($image, 35, 0, 42, 78 + ($i * 64), $white, $font, $text);


        imagejpeg($canvas);
//        exit();
    }

    private static function appendOwner($canvas, GroupShoot $groupShoot, $shareUserId)
    {
        $nickname = $groupShoot->owner->nickname;
        $avatar   = $groupShoot->owner->avatar;

        $shareUser = User::find($shareUserId);
        //If the user had joined the parent groupshoot,append the share user.
        if ($hadShareUserJoinedGroupShoot = $shareUser->hadJoinedGroupShoot($groupShoot)) {
            $nickname = $shareUser->nickname;
            $avatar   = $shareUser->avatar;
        }

        if ($groupShoot->ownedByUser($shareUserId)) {
            $joinOrCreateGroupShootText = '发起';
        }
        else {
            $joinOrCreateGroupShootText = $hadShareUserJoinedGroupShoot ? '参与' : '发起';
        }

        $avatarUrl   = str_replace('http://', 'https://', $avatar);
        $avatarImage = self::cropIntoCircle($avatarUrl);
        imagecopymerge($canvas, $avatarImage, 18, (self::IMAGE_HEIGHT * 2) + 14, 0, 0, 44, 44, 100);

        $fontColor = imagecolorallocate($canvas, 16, 16, 17);
        imagettftext($canvas, 14, 0, 72, 335 + 14, $fontColor, base_path() . '/app/PingFang-Bold.ttf', $nickname);

        imagettftext($canvas, 14, 0, 18, 404, $fontColor, base_path() . '/app/PingFang-Bold.ttf', $groupShoot->title);

    }

    private static function appendMergedCovers($canvas, $imageUrls)
    {
        $image = imagecreatefrompng($imageUrls[0]);
        imagecopymerge($canvas, $image, self::START_X, self::START_Y, 0, 0, self::IMAGE_WIDTH, self::IMAGE_HEIGHT, 100);

        $image = imagecreatefrompng($imageUrls[1]);
        imagecopymerge($canvas, $image, self::START_X + self::IMAGE_WIDTH + self::MARGIN_WIDTH, self::START_Y, 0, 0, self::IMAGE_WIDTH, self::IMAGE_HEIGHT, 100);

        $image = imagecreatefrompng($imageUrls[2]);
        imagecopymerge($canvas, $image, self::START_X, self::START_Y + self::IMAGE_HEIGHT + self::MARGIN_HEIGHT, 0, 0, self::IMAGE_WIDTH, self::IMAGE_HEIGHT, 100);

        $image = imagecreatefrompng($imageUrls[3]);
        imagecopymerge($canvas, $image, self::START_X + self::IMAGE_WIDTH + self::MARGIN_WIDTH, self::START_Y + self::IMAGE_HEIGHT + self::MARGIN_HEIGHT,
            0, 0, self::IMAGE_WIDTH, self::IMAGE_HEIGHT, 100);
    }

    private static function createCanvas()
    {
        $canvasWidth  = self::IMAGE_WIDTH * 2 + self::MARGIN_WIDTH;
        $canvasHeight = self::IMAGE_WIDTH * 2 + self::MARGIN_HEIGHT + self::GROUP_SHOOT_INFO_AREA_HEIGHT;
        $canvas       = imagecreatetruecolor($canvasWidth, $canvasHeight);
        $black        = imagecolorallocate($canvas, 0, 0, 0);
        imagefill($canvas, 0, 0, $black);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, ($canvasHeight - self::GROUP_SHOOT_INFO_AREA_HEIGHT), $canvasWidth, $canvasHeight, $white);
        return $canvas;
    }


}
