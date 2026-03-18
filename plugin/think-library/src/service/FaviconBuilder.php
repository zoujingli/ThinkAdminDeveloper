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

namespace think\admin\service;

use think\admin\Exception;

/**
 * 标准 favicon 构建工具。
 */
final class FaviconBuilder
{
    /**
     * 转换后的 BMP 图像。
     */
    private array $images = [];

    /**
     * 创建新的 ICO 生成器。
     *
     * @throws Exception
     */
    public function __construct(?string $file = null, array $size = [])
    {
        $this->assertGdFunctions();
        if (is_string($file)) {
            $this->addImage($file, $size);
        }
    }

    /**
     * 添加图像到生成器中。
     *
     * @throws Exception
     */
    public function addImage(string $file, array $size = []): static
    {
        $im = $this->loadImageFile($file);
        if ($im === false) {
            throw new Exception(lang('Read picture file Failed.'));
        }
        if (empty($size)) {
            $size = [imagesx($im), imagesy($im)];
        }
        [$width, $height] = $size;
        $image = imagecreatetruecolor($width, $height);
        imagecolortransparent($image, imagecolorallocatealpha($image, 0, 0, 0, 127));
        imagealphablending($image, false);
        imagesavealpha($image, true);
        if (imagecopyresampled($image, $im, 0, 0, 0, 0, $width, $height, imagesx($im), imagesy($im)) === false) {
            throw new Exception(lang('Parse and process picture Failed.'));
        }
        $this->addImageData($image);
        return $this;
    }

    /**
     * 将 ICO 内容写入到文件。
     */
    public function saveIco(string $file): bool
    {
        if (false === ($data = $this->getIcoData())) {
            return false;
        }
        if (false === ($fh = fopen($file, 'w'))) {
            return false;
        }
        if (fwrite($fh, $data) === false) {
            fclose($fh);
            return false;
        }
        fclose($fh);
        return true;
    }

    /**
     * 检查 GD 依赖函数是否可用。
     *
     * @throws Exception
     */
    private function assertGdFunctions(): void
    {
        $functions = [
            'imagesx',
            'imagesy',
            'getimagesize',
            'imagesavealpha',
            'imagecreatefromstring',
            'imagecreatetruecolor',
            'imagecolortransparent',
            'imagecolorallocatealpha',
            'imagecopyresampled',
            'imagealphablending',
        ];
        foreach ($functions as $function) {
            if (!function_exists($function)) {
                throw new Exception(lang('Required %s function not found.', [$function]));
            }
        }
    }

    /**
     * 读取图片资源。
     *
     * @return false|\GdImage|resource
     */
    private function loadImageFile(string $file)
    {
        if (getimagesize($file) === false) {
            return false;
        }
        if (false === ($data = file_get_contents($file))) {
            return false;
        }
        if (false === ($image = @imagecreatefromstring($data))) {
            return false;
        }
        return $image;
    }

    /**
     * 将 GD 图像转为 BMP 格式。
     *
     * @param mixed $im
     */
    private function addImageData($im): void
    {
        [$width, $height] = [imagesx($im), imagesy($im)];
        [$pixelData, $opacityData, $opacityValue] = [[], [], 0];
        for ($y = $height - 1; $y >= 0; --$y) {
            for ($x = 0; $x < $width; ++$x) {
                $color = imagecolorat($im, $x, $y);
                $alpha = ($color & 0x7F000000) >> 24;
                $alpha = (1 - ($alpha / 127)) * 255;
                $color &= 0xFFFFFF;
                $color |= 0xFF000000 & ((int)$alpha << 24);
                $pixelData[] = $color;
                $opacity = ($alpha <= 127) ? 1 : 0;
                $opacityValue = ($opacityValue << 1) | $opacity;
                if ((($x + 1) % 32) === 0) {
                    $opacityData[] = $opacityValue;
                    $opacityValue = 0;
                }
            }
            if (($x % 32) > 0) {
                while (($x++ % 32) > 0) {
                    $opacityValue <<= 1;
                }
                $opacityData[] = $opacityValue;
                $opacityValue = 0;
            }
        }
        $imageHeaderSize = 40;
        $colorMaskSize = $width * $height * 4;
        $opacityMaskSize = (ceil($width / 32) * 4) * $height;
        $data = pack('VVVvvVVVVVV', 40, $width, $height * 2, 1, 32, 0, 0, 0, 0, 0, 0);
        foreach ($pixelData as $color) {
            $data .= pack('V', $color);
        }
        foreach ($opacityData as $opacity) {
            $data .= pack('N', $opacity);
        }
        $this->images[] = [
            'data' => $data,
            'size' => $imageHeaderSize + $colorMaskSize + $opacityMaskSize,
            'width' => $width,
            'height' => $height,
            'pixel' => 32,
            'colors' => 0,
        ];
    }

    /**
     * 生成并获取 ICO 图像数据。
     */
    private function getIcoData()
    {
        if (empty($this->images)) {
            return false;
        }
        [$pixelData, $entrySize] = ['', 16];
        $data = pack('vvv', 0, 1, count($this->images));
        $offset = 6 + ($entrySize * count($this->images));
        foreach ($this->images as $image) {
            $data .= pack('CCCCvvVV', $image['width'], $image['height'], $image['colors'], 0, 1, $image['pixel'], $image['size'], $offset);
            $pixelData .= $image['data'];
            $offset += $image['size'];
        }
        return $data . $pixelData;
    }
}
