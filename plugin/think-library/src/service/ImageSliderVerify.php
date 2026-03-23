<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdminDeveloper
 * +----------------------------------------------------------------------
 * | Copyright (c) 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | Official Website: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | Licensed: https://mit-license.org
 * | Disclaimer: https://thinkadmin.top/disclaimer
 * | Vip Rights: https://thinkadmin.top/vip-introduce
 * +----------------------------------------------------------------------
 * | Gitee Repository: https://gitee.com/zoujingli/ThinkAdmin
 * | Github Repository: https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

namespace think\admin\service;

use think\admin\extend\CodeToolkit;
use think\admin\Library;

/**
 * 标准滑块验证码工具。
 */
final class ImageSliderVerify
{
    /**
     * 浮层圆半径。
     */
    private int $r = 10;

    /**
     * 图片路径。
     */
    private string $srcImage;

    /**
     * 浮层图宽高。
     */
    private int $picWidth = 100;

    private int $picHeight = 100;

    /**
     * 目标图宽高。
     */
    private int $dstWidth = 600;

    private int $dstHeight = 300;

    /**
     * 验证器构造方法。
     */
    public function __construct(string $image, array $options = [])
    {
        foreach ($options as $k => $v) {
            if (property_exists($this, $k)) {
                $this->{$k} = $v;
            }
        }
        $this->srcImage = $image;
    }

    /**
     * 生成图片拼图。
     *
     * @return array [code, bgimg, water]
     */
    public static function render(string $image, int $time = 1800, int $diff = 10, int $retry = 3): array
    {
        $data = (new self($image))->create();
        $range = [$data['point'] - $diff, $data['point'] + $diff];
        $result = ['retry' => $retry, 'error' => 0, 'expire' => time() + $time, 'range' => $range];
        Library::$sapp->cache->set($code = CodeToolkit::uniqidNumber(16, 'V'), $result, $time);
        return [
            'code' => $code,
            'bgimg' => $data['bgimg'],
            'water' => $data['water'],
            'width' => $data['width'],
            'height' => $data['height'],
            'piece_width' => $data['piece_width'],
        ];
    }

    /**
     * 创建背景图和浮层图、浮层图 X 坐标。
     *
     * @return array [point, bgimg, water]
     */
    public function create(): array
    {
        $dstim = $this->cover($this->srcImage, $this->dstWidth, $this->dstHeight);
        $watim = imagecreatetruecolor($this->picWidth, $this->dstHeight);
        imagesavealpha($watim, true) && imagealphablending($watim, false);
        imagefill($watim, 0, 0, imagecolorallocatealpha($watim, 255, 255, 255, 127));

        $srcX1 = mt_rand(150, $this->dstWidth - $this->picWidth);
        $srcY1 = mt_rand(0, $this->dstHeight - $this->picHeight);

        $borders = [
            imagecolorallocatealpha($dstim, 250, 100, 0, 50),
            imagecolorallocatealpha($dstim, 250, 0, 100, 50),
            imagecolorallocatealpha($dstim, 100, 0, 250, 50),
            imagecolorallocatealpha($dstim, 100, 250, 0, 50),
            imagecolorallocatealpha($dstim, 0, 250, 100, 50),
        ];
        shuffle($borders);
        $c1 = array_pop($borders);
        $gray = imagecolorallocatealpha($dstim, 0, 0, 0, 80);
        $blue = imagecolorallocatealpha($watim, 0, 100, 250, 50);
        $waters = $this->withWaterPoint();

        for ($i = 0; $i < $this->picHeight; ++$i) {
            for ($j = 0; $j < $this->picWidth; ++$j) {
                if ($waters[$i][$j] === 1) {
                    if (
                        empty($waters[$i - 1][$j - 1]) || empty($waters[$i - 2][$j - 2])
                        || empty($waters[$i + 1][$j + 1]) || empty($waters[$i + 2][$j + 2])
                    ) {
                        imagesetpixel($watim, $j, $srcY1 + $i, $blue);
                    } else {
                        imagesetpixel($watim, $j, $srcY1 + $i, imagecolorat($dstim, $srcX1 + $j, $srcY1 + $i));
                    }
                }
            }
        }

        for ($i = 0; $i < $this->picHeight; ++$i) {
            for ($j = 0; $j < $this->picWidth; ++$j) {
                if ($waters[$i][$j] === 1) {
                    if (
                        empty($waters[$i - 1][$j - 1]) || empty($waters[$i - 2][$j - 2])
                        || empty($waters[$i + 1][$j + 1]) || empty($waters[$i + 2][$j + 2])
                    ) {
                        imagesetpixel($dstim, $srcX1 + $j, $srcY1 + $i, $c1);
                    } else {
                        imagesetpixel($dstim, $srcX1 + $j, $srcY1 + $i, $gray);
                    }
                }
            }
        }

        [, , $bgimg] = [ob_start(), imagepng($dstim), ob_get_contents(), ob_end_clean(), imagedestroy($dstim)];
        [, , $water] = [ob_start(), imagepng($watim), ob_get_contents(), ob_end_clean(), imagedestroy($watim)];

        return [
            'point' => $srcX1,
            'bgimg' => 'data:image/png;base64,' . base64_encode($bgimg),
            'water' => 'data:image/png;base64,' . base64_encode($water),
            'width' => $this->dstWidth,
            'height' => $this->dstHeight,
            'piece_width' => $this->picWidth,
        ];
    }

    /**
     * 居中裁剪图片。
     *
     * @return \GdImage|resource
     */
    public static function cover(string $image, int $width, int $height)
    {
        $file = self::coverFile($image, $width, $height);
        if (is_file($file) && ($data = file_get_contents($file)) !== false) {
            if (($cached = imagecreatefromstring($data)) !== false) {
                return $cached;
            }
        }
        [$w, $h, $srcim] = self::sourceImage($image) ?? self::fallbackSource($image, $width, $height);
        if ($w > $h) {
            [$_sw, $_sh, $_sx, $_sy] = [$h, $h, (int)(($w - $h) / 2), 0];
        } elseif ($w < $h) {
            [$_sw, $_sh, $_sx, $_sy] = [$w, $w, 0, (int)(($h - $w) / 2)];
        } else {
            [$_sw, $_sh, $_sx, $_sy] = [$w, $h, 0, 0];
        }
        $newim = imagecreatetruecolor($width, $height);
        imagecopyresampled($newim, $srcim, 0, 0, $_sx, $_sy, $width, $height, $_sw, $_sh);
        imagedestroy($srcim);
        self::storeCover($newim, $file);
        return $newim;
    }

    /**
     * 在线验证是否通过。
     * 返回值约定：
     * `-1` 需要刷新，`0` 验证失败，`1` 验证成功。
     */
    public static function verify(string $code, string $value, bool $clear = false): int
    {
        $cache = Library::$sapp->cache->get($code);
        if (empty($cache['range']) || empty($cache['retry'])) {
            return -1;
        }
        if ($cache['range'][0] <= $value && $value <= $cache['range'][1]) {
            if ($clear) {
                Library::$sapp->cache->delete($code);
            }
            return 1;
        }
        if (++$cache['error'] < $cache['retry']) {
            if (($ttl = $cache['expire'] - time()) > 0) {
                Library::$sapp->cache->set($code, $cache, $ttl);
                return 0;
            }
        }
        Library::$sapp->cache->delete($code);
        return -1;
    }

    /**
     * 生成裁剪缓存文件。
     */
    private static function coverFile(string $image, int $width, int $height): string
    {
        clearstatcache(true, $image);
        $mtime = is_file($image) ? filemtime($image) : 0;
        $size = is_file($image) ? filesize($image) : 0;
        $hash = hash('sha256', "{$image}#{$mtime}#{$size}#{$width}#{$height}");
        return runpath('runtime/slider/' . substr($hash, 0, 2) . '/' . $hash . '.png');
    }

    /**
     * 读取源图片资源。
     *
     * @return array{0:int,1:int,2:\GdImage|resource}|null
     */
    private static function sourceImage(string $image): ?array
    {
        clearstatcache(true, $image);
        if (!is_file($image) || !is_readable($image)) {
            return null;
        }
        $size = @getimagesize($image);
        if (!is_array($size) || empty($size[0]) || empty($size[1])) {
            return null;
        }
        $data = file_get_contents($image);
        if ($data === false) {
            return null;
        }
        $srcim = @imagecreatefromstring($data);
        if ($srcim === false) {
            return null;
        }
        return [intval($size[0]), intval($size[1]), $srcim];
    }

    /**
     * 在静态资源缺失时生成兜底背景图。
     *
     * @return array{0:int,1:int,2:\GdImage|resource}
     */
    private static function fallbackSource(string $image, int $width, int $height): array
    {
        $hash = hash('sha256', $image);
        $palettes = [
            [[22, 66, 122], [120, 188, 222]],
            [[31, 84, 68], [194, 235, 191]],
            [[88, 44, 24], [236, 183, 90]],
            [[58, 34, 96], [233, 205, 146]],
            [[24, 58, 94], [218, 237, 255]],
        ];
        [$start, $end] = $palettes[hexdec(substr($hash, 0, 2)) % count($palettes)];
        $imageim = imagecreatetruecolor($width, $height);
        imagealphablending($imageim, true);
        imagesavealpha($imageim, true);

        $scale = max($height - 1, 1);
        for ($y = 0; $y < $height; ++$y) {
            $color = imagecolorallocate(
                $imageim,
                (int)round($start[0] + ($end[0] - $start[0]) * $y / $scale),
                (int)round($start[1] + ($end[1] - $start[1]) * $y / $scale),
                (int)round($start[2] + ($end[2] - $start[2]) * $y / $scale),
            );
            imageline($imageim, 0, $y, $width, $y, $color);
        }

        $line = imagecolorallocatealpha($imageim, 255, 255, 255, 96);
        for ($x = -$height; $x < $width + $height; $x += 48) {
            imageline($imageim, $x, 0, $x + $height, $height, $line);
        }

        for ($i = 0; $i < 4; ++$i) {
            $offset = 2 + $i * 2;
            $radius = 60 + hexdec(substr($hash, $offset, 2)) % 60;
            $alpha = 88 + hexdec(substr($hash, $offset + 8, 2)) % 24;
            $shape = imagecolorallocatealpha($imageim, 255, 255, 255, $alpha);
            imagefilledellipse(
                $imageim,
                (int)(($i + 1) * $width / 5),
                30 + hexdec(substr($hash, $offset + 16, 2)) % max($height - 60, 1),
                $radius * 2,
                $radius * 2,
                $shape
            );
        }

        return [$width, $height, $imageim];
    }

    /**
     * 保存裁剪缓存文件。
     */
    private static function storeCover($image, string $file): void
    {
        is_dir($dir = dirname($file)) || mkdir($dir, 0755, true);
        imagepng($image, $file);
    }

    /**
     * 计算水印矩阵坐标。
     */
    private function withWaterPoint(): array
    {
        $waters = [];
        $dr = $this->r * $this->r;
        $lw = $this->r * 2 - 5;
        $c1x = $lw + ($this->picWidth - $lw * 2) / 2;
        $c1y = $this->r;
        $c2x = $this->picHeight - $this->r;
        $c2y = $lw + ($this->picHeight - $lw * 2) / 2;

        for ($i = 0; $i < $this->picHeight; ++$i) {
            for ($j = 0; $j < $this->picWidth; ++$j) {
                $d1 = pow($j - $c1x, 2) + pow($i - $c1y, 2);
                $d2 = pow($j - $c2x, 2) + pow($i - $c2y, 2);
                $waters[$i][$j] = (($i >= $lw && $j >= $lw && $i <= $this->picHeight - $lw && $j <= $this->picWidth - $lw) || $d1 <= $dr || $d2 <= $dr) ? 1 : 0;
            }
        }
        return $waters;
    }
}
