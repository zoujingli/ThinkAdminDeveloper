<?php

// +----------------------------------------------------------------------
// | WeMall Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wemall
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wemall
// +----------------------------------------------------------------------


declare (strict_types=1);

namespace plugin\wemall\service;

use app\wechat\service\MediaService;
use app\wechat\service\WechatService;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\Label\Font\Font;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\Result\ResultInterface;
use plugin\account\service\Account;
use plugin\account\service\contract\AccountInterface;
use think\admin\Exception;
use think\admin\Library;
use think\admin\Service;
use think\admin\Storage;
use WeMini\Qrcode;

/**
 * 海报图片生成服务
 * @class PosterService
 * @package plugin\wuma\service
 */
abstract class PosterService extends Service
{

    /**
     * 获取字体路径
     * @return string
     */
    public static function font(): string
    {
        return __DIR__ . '/extra/font01.ttf';
    }

    /**
     * 生成二维码接口
     * @param string $text 二维码文本内容
     * @param string $label 二维码标签内容
     * @param integer $labelSize 二维码标签大小
     * @return \Endroid\QrCode\Writer\Result\ResultInterface
     */
    public static function qrcode(string $text, string $label = '', int $labelSize = 12): ResultInterface
    {
        $qrcode = Builder::create()->data($text)->size(300)->margin(15)
            ->writer(new PngWriter())->encoding(new Encoding('UTF-8'))
            ->writerOptions([])->validateResult(false)
            ->roundBlockSizeMode(new RoundBlockSizeModeMargin())
            ->errorCorrectionLevel(new ErrorCorrectionLevelHigh());

        // 给二维码设置标签
        if (strlen($label) > 0) {
            $qrcode->labelText($label)->labelFont(new Font(self::font(), $labelSize));
        }

        return $qrcode->build();
    }

    /**
     * 创建海报内容
     * @param string $target
     * @param array $items
     * @param array $extra
     * @return string
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     * @throws \think\admin\Exception
     */
    public static function create(string $target, array $items, array $extra = []): string
    {
        $args = func_get_args();
        $args[] = ConfigService::get('base_domain');
        $name = Storage::name(json_encode($args, 64 | 256), 'png', 'poster');
        if (empty($info = Storage::info($name))) {
            self::build($target, $items, $extra, $image);
            $info = Storage::set($name, $image);
        }
        return $info['url'] ?? $target;
    }

    /**
     * 海报图片图片
     * @param string $target 背景图片
     * @param array $items 配置参数
     * @param array $extra 自定内容
     * @param string|null $image 合成图片
     * @return string
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     * @throws \think\admin\Exception
     */
    public static function build(string $target, array $items, array $extra = [], ?string &$image = null): string
    {
        $zoom = 1.5;
        $file = Storage::down($target)['file'] ?? '';
        if (empty($file) || !file_exists($file) || filesize($file) < 10) {
            throw new Exception('读取底图失败！');
        }
        // 加载背景图
        [$tw, $th] = [intval(504 * $zoom), intval(713 * $zoom)];
        [$font, $target] = [self::font(), imagecreatetruecolor($tw, $th)];
        $source = self::imageReset(imagecreatefromstring(file_get_contents($file)), 1024);
        [$sw, $wh] = [imagesx($source), imagesy($source)];
        imagecopyresampled($target, $source, 0, 0, 0, 0, $tw, $th, $sw, $wh);
        foreach ($items as $item) if ($item['state']) {
            [$size, $item['value']] = [intval($item['size']), $extra[$item['rule']] ?? $item['value']];
            [$x, $y] = [intval($tw * $item['point']['x'] / 100), intval($th * $item['point']['y'] / 100)];
            if ($item['type'] === 'ximg') {
                $simg = self::imageCorners(self::imageReset(self::createImage($item, $extra), 300), 12);
                imagecopyresampled($target, $simg, $x, $y, 0, 0, intval($size * $zoom), intval($size * $zoom), imagesx($simg), imagesy($simg));
                imagedestroy($simg);
            } else {
                if (preg_match('|^rgba\(\s*([\d.]+),\s*([\d.]+),\s*([\d.]+),\s*([\d.]+)\)$|', $item['color'], $matchs)) {
                    [, $r, $g, $b, $a] = $matchs;
                    $black = imagecolorallocatealpha($target, intval($r), intval($g), intval($b), (1 - $a) * 127);
                } else {
                    $black = imagecolorallocate($target, 0x00, 0x00, 0x00);
                }
                imagefttext($target, $size, 0, $x, intval($y + $size / 2 + 16), $black, $font, $item['value']);
            }
        }
        ob_start() && imagepng($target) && ($image = ob_get_contents());
        ob_end_clean() && imagedestroy($target) && imagedestroy($source);
        return sprintf("data:image/png;base64,%s", base64_encode($image));
    }

    /**
     * 创建其他图形对象
     * @param array $item
     * @param array $extra
     * @return false|\GdImage|resource
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     * @throws \think\admin\Exception
     */
    private static function createImage(array $item, array $extra = [])
    {
        if ($item['rule'] === 'user.spreat' || stripos($item['rule'], 'qrcode') !== false) {
            // 当前访问终端
            /** @var AccountInterface $account */
            $account = sysvar('plugin_account_object');
            if ($account instanceof AccountInterface) {
                $type = $account->getType();
                $unid = $account->getUnid();
                $code = $account->getCode();
            } else {
                $type = sysvar('plugin_account_user_type') ?: '';
                $code = sysvar('plugin_account_user_code') ?: '';
                $unid = sysvar('plugin_account_user_unid') ?: 0;
            }

            // 动态计算推荐链接
            $link = $item['value'] ?: (empty($extra['user.spreat']) ? '/pages/home/index?from=UNID&fuser=CODE' : $extra['user.spreat']);
            if (stripos($link, 'from=') === false) $link .= (strpos($link, '?') === false ? '?' : '&') . 'from=UNID';
            $link = str_replace(['UNID', 'CODE'], [strval($unid), $code], $link);
            // 根据环境生成二维码
            if ($type === Account::WXAPP) {
                // 微信小程序二维码
                $qrcode = Qrcode::instance(WechatService::getWxconf())->createMiniPath($link);
            } else {
                // 生成网页访问二维码
                $link = rtrim(ConfigService::get('base_domain'), '\\/') . $link;
            }
            //elseif (in_array($type, [Account::WAP, Account::WEB, Account::WECHAT, Account::ANDROID])) {
            //}
//            elseif ($type === Account::ANDROID) {
//                $urlscheme = ConfigService::get('scheme_android') ?: 'thinkadminmobile';
//                $link = "{$urlscheme}://pages/home/index?from={$unid}";
//            }
            // 动态读取二维码内容
            if (!empty($qrcode) || !empty($extra['user.qrcode']) && !empty($qrcode = Library::$sapp->cache->get($extra['user.qrcode']))) {
                return imagecreatefromstring($qrcode);
            } else {
                return imagecreatefromstring(MediaService::getQrcode($link)->getString());
            }
        } else {
            $file = Storage::down($item['value'] ?: Account::headimg())['file'] ?? '';
            if (empty($file) || !is_file($file) || filesize($file) < 10) {
                throw new Exception('读取图片失败！');
            }
            return imagecreatefromstring(file_get_contents($file));
        }
    }

    /**
     * 按最小边绽放图片
     * @param false|\GdImage|resource $image
     * @param integer $size
     * @return false|\GdImage|resource
     */
    private static function imageReset($image, int $size)
    {
        // 计算缩放比例
        [$iw, $ih] = [imagesx($image), imagesy($image)];
        $scale = min($iw, $ih) / $size;
        // 计算新宽度和高度
        [$nw, $nh] = [intval($iw / $scale), intval($ih / $scale)];
        $target = imagecreatetruecolor($nw, $nh);
        imagecopyresampled($target, $image, 0, 0, 0, 0, $nw, $nh, $iw, $ih);
        imagedestroy($image);
        return $target;
    }

    /**
     * 图片圆角处理
     * @param false|\GdImage|resource $image
     * @param int $radius
     * @return false|\GdImage|resource
     */
    private static function imageCorners($image, int $radius = 10)
    {
        [$ws, $hs] = [imagesx($image), imagesy($image)];

        $corner = $radius + 2;
        $s = $corner * 2;

        $src = imagecreatetruecolor($s, $s);
        imagecopy($src, $image, 0, 0, 0, 0, $corner, $corner);
        imagecopy($src, $image, $corner, 0, $ws - $corner, 0, $corner, $corner);
        imagecopy($src, $image, $corner, $corner, $ws - $corner, $hs - $corner, $corner, $corner);
        imagecopy($src, $image, 0, $corner, 0, $hs - $corner, $corner, $corner);

        $q = 8; # change this if you want
        $radius *= $q;

        # find unique color
        do [$r, $g, $b] = [rand(0, 255), rand(0, 255), rand(0, 255)];
        while (imagecolorexact($src, $r, $g, $b) < 0);

        $ns = $s * $q;

        $img = imagecreatetruecolor($ns, $ns);
        $alphacolor = imagecolorallocatealpha($img, $r, $g, $b, 127);
        imagealphablending($img, false);
        imagefilledrectangle($img, 0, 0, $ns, $ns, $alphacolor);

        imagefill($img, 0, 0, $alphacolor);
        imagecopyresampled($img, $src, 0, 0, 0, 0, $ns, $ns, $s, $s);
        imagedestroy($src);

        imagearc($img, $radius - 1, $radius - 1, $radius * 2, $radius * 2, 180, 270, $alphacolor);
        imagefilltoborder($img, 0, 0, $alphacolor, $alphacolor);
        imagearc($img, $ns - $radius, $radius - 1, $radius * 2, $radius * 2, 270, 0, $alphacolor);
        imagefilltoborder($img, $ns - 1, 0, $alphacolor, $alphacolor);
        imagearc($img, $radius - 1, $ns - $radius, $radius * 2, $radius * 2, 90, 180, $alphacolor);
        imagefilltoborder($img, 0, $ns - 1, $alphacolor, $alphacolor);
        imagearc($img, $ns - $radius, $ns - $radius, $radius * 2, $radius * 2, 0, 90, $alphacolor);
        imagefilltoborder($img, $ns - 1, $ns - 1, $alphacolor, $alphacolor);
        imagealphablending($img, true);
        imagecolortransparent($img, $alphacolor);

        # resize image down
        $dest = imagecreatetruecolor($s, $s);
        imagealphablending($dest, false);
        imagefilledrectangle($dest, 0, 0, $s, $s, $alphacolor);
        imagecopyresampled($dest, $img, 0, 0, 0, 0, $s, $s, $ns, $ns);
        imagedestroy($img);

        # output image
        imagealphablending($image, false);
        imagecopy($image, $dest, 0, 0, 0, 0, $corner, $corner);
        imagecopy($image, $dest, $ws - $corner, 0, $corner, 0, $corner, $corner);
        imagecopy($image, $dest, $ws - $corner, $hs - $corner, $corner, $corner, $corner, $corner);
        imagecopy($image, $dest, 0, $hs - $corner, 0, $corner, $corner, $corner);
        imagealphablending($image, true);
        imagedestroy($dest);
        return $image;
    }
}


