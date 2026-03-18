<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdmin
 * +----------------------------------------------------------------------
 * | 版权所有 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | 官方网站: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | 开源协议 ( https://mit-license.org )
 * | 免责声明 ( https://thinkadmin.top/disclaimer )
 * | 会员特权 ( https://thinkadmin.top/vip-introduce )
 * +----------------------------------------------------------------------
 * | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
 * | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

namespace plugin\wuma\service;

use think\admin\Exception;
use think\admin\Service;
use think\admin\Storage;

/**
 * 证书图片生成服务
 * @class CertService
 */
class CertService extends Service
{
    /**
     * 绘制证书图片.
     * @throws Exception
     */
    public static function create(string $target, array $items): string
    {
        $file = Storage::down($target)['file'];
        if (empty($file) || !file_exists($file) || filesize($file) < 10) {
            throw new Exception('读取图片模板失败！');
        }
        // 加载背景图
        [$sw, $wh] = getimagesize($file);
        [$tw, $th] = [intval(504 * 1.5), intval(713 * 1.5)];
        $font = __DIR__ . '/extra/font01.ttf';
        $target = imagecreatetruecolor($tw, $th);
        $source = imagecreatefromstring(file_get_contents($file));
        imagecopyresampled($target, $source, 0, 0, 0, 0, $tw, $th, $sw, $wh);
        foreach ($items as $item) {
            if ($item['state']) {
                [$x, $y] = [intval($tw * $item['point']['x'] / 100), intval($th * $item['point']['y'] / 100)];
                if (preg_match('|^rgba\(\s*([\d.]+),\s*([\d.]+),\s*([\d.]+),\s*([\d.]+)\)$|', $item['color'], $matchs)) {
                    [, $r, $g, $b, $a] = $matchs;
                    $black = imagecolorallocatealpha($target, intval($r), intval($g), intval($b), (1 - $a) * 127);
                } else {
                    $black = imagecolorallocate($target, 0x00, 0x00, 0x00);
                }
                imagefttext($target, $item['size'], 0, $x, intval($y + $item['size'] / 2 + 16), $black, $font, $item['value']);
            }
        }
        ob_start();
        imagepng($target);
        $base64 = base64_encode(ob_get_contents());
        ob_end_clean();
        imagedestroy($target);
        imagedestroy($source);
        return "data:image/png;base64,{$base64}";
    }
}
