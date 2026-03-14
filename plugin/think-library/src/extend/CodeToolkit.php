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

namespace think\admin\extend;

/**
 * 标准编码工具。
 * `extend` 只保留无状态、跨组件可复用的基础工具。
 */
class CodeToolkit
{
    /**
     * 生成 UUID 编码。
     */
    public static function uuid(): string
    {
        $chars = md5(uniqid((string)mt_rand(0, 9999), true));
        $value = substr($chars, 0, 8) . '-' . substr($chars, 8, 4) . '-';
        $value .= substr($chars, 12, 4) . '-' . substr($chars, 16, 4) . '-';
        return strtoupper($value . substr($chars, 20, 12));
    }

    /**
     * 生成随机编码。
     * `type` 仅允许数字、字母、数字字母三种规则，避免继续引入隐式分支。
     */
    public static function random(int $size = 10, int $type = 1, string $prefix = ''): string
    {
        $chars = static::alphabet($type);
        $code = $prefix . $chars[mt_rand(1, strlen($chars) - 1)];
        while (strlen($code) < $size) {
            $code .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $code;
    }

    /**
     * 生成日期编码。
     */
    public static function uniqidDate(int $size = 16, string $prefix = ''): string
    {
        $size = max($size, 14);
        $code = $prefix . date('Ymd') . ((string)((int)date('H') + (int)date('i'))) . date('s');
        while (strlen($code) < $size) {
            $code .= mt_rand(0, 9);
        }
        return $code;
    }

    /**
     * 生成数字编码。
     */
    public static function uniqidNumber(int $size = 12, string $prefix = ''): string
    {
        $size = max($size, 10);
        $time = (string)time();
        $code = $prefix . ((int)$time[0] + (int)$time[1]) . substr($time, 2) . mt_rand(0, 9);
        while (strlen($code) < $size) {
            $code .= mt_rand(0, 9);
        }
        return $code;
    }

    /**
     * 文本转码。
     * 先处理 BOM，再回退到 mb_detect_encoding，减少乱码概率。
     */
    public static function text2utf8(string $text, string $target = 'UTF-8'): string
    {
        return mb_convert_encoding($text, $target, static::detectEncoding($text));
    }

    /**
     * 数据加密处理。
     * 这里保留历史序列化格式，避免影响现有 JWT 和业务临时票据。
     *
     * @param mixed $data
     */
    public static function encrypt($data, string $skey): string
    {
        $iv = static::random(16, 3);
        $value = openssl_encrypt(serialize($data), 'AES-256-CBC', $skey, 0, $iv);
        return static::enSafe64((string)json_encode(['iv' => $iv, 'value' => $value]));
    }

    /**
     * 数据解密处理。
     *
     * @return mixed
     */
    public static function decrypt(string $data, string $skey)
    {
        $attr = json_decode(static::deSafe64($data), true) ?: [];
        return unserialize(openssl_decrypt((string)($attr['value'] ?? ''), 'AES-256-CBC', $skey, 0, (string)($attr['iv'] ?? '')));
    }

    /**
     * Base64Url 安全编码。
     */
    public static function enSafe64(string $text): string
    {
        return rtrim(strtr(base64_encode($text), '+/', '-_'), '=');
    }

    /**
     * Base64Url 安全解码。
     */
    public static function deSafe64(string $text): string
    {
        return base64_decode(str_pad(strtr($text, '-_', '+/'), (int)(ceil(strlen($text) / 4) * 4), '='));
    }

    /**
     * 压缩数据对象。
     *
     * @param mixed $data
     */
    public static function enzip($data): string
    {
        return static::enSafe64(gzcompress(serialize($data)));
    }

    /**
     * 解压数据对象。
     *
     * @return mixed
     */
    public static function dezip(string $string)
    {
        return unserialize(gzuncompress(static::deSafe64($string)));
    }

    /**
     * 根据规则选择随机字符集。
     */
    private static function alphabet(int $type): string
    {
        $numbers = '0123456789';
        $letters = 'abcdefghijklmnopqrstuvwxyz';
        if ($type === 1) {
            return $numbers;
        }
        if ($type === 3) {
            return $numbers . $letters;
        }
        return $letters;
    }

    /**
     * 尝试通过 BOM 判断文本编码。
     */
    private static function detectEncoding(string $text): string
    {
        [$first2, $first4] = [substr($text, 0, 2), substr($text, 0, 4)];
        if ($first4 === chr(0x00) . chr(0x00) . chr(0xFE) . chr(0xFF)) {
            return 'UTF-32BE';
        }
        if ($first4 === chr(0xFF) . chr(0xFE) . chr(0x00) . chr(0x00)) {
            return 'UTF-32LE';
        }
        if ($first2 === chr(0xFE) . chr(0xFF)) {
            return 'UTF-16BE';
        }
        if ($first2 === chr(0xFF) . chr(0xFE)) {
            return 'UTF-16LE';
        }
        return mb_detect_encoding($text) ?: 'UTF-8';
    }
}
