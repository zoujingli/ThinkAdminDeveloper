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

namespace app\admin\service;

use think\admin\Storage;

/**
 * 上传文件安全检查.
 * @class UploadSecurity
 */
final class UploadSecurity
{
    /**
     * 可能被 Web 服务器、脚本解释器或操作系统执行的后缀.
     */
    private const EXECUTABLE_EXTENSIONS = [
        'asp', 'aspx', 'asa', 'asax', 'ashx', 'asmx',
        'bat', 'bash', 'cgi', 'cmd', 'com', 'dll', 'dylib', 'exe',
        'jar', 'jsp', 'jspx', 'msi', 'pht', 'phtm', 'phtml', 'phar',
        'php', 'php2', 'php3', 'php4', 'php5', 'php6', 'php7', 'php8', 'phps',
        'pl', 'py', 'rb', 'sh', 'shtm', 'shtml', 'so', 'zsh',
    ];

    /**
     * 检查上传键是否为规范的相对文件路径.
     */
    public static function isNameSafe(string $name, string $extension): bool
    {
        $extension = strtolower(trim($extension, ". \\/\\\t\n\r\0\x0B"));
        if ($extension === '' || !self::isPathSafe($name)) {
            return false;
        }
        return strtolower(pathinfo($name, PATHINFO_EXTENSION)) === $extension;
    }

    /**
     * 检查文件后缀是否允许存储.
     */
    public static function isExtensionSafe(string $extension): bool
    {
        $extension = strtolower(trim($extension, ". \\/\\\t\n\r\0\x0B"));
        if (preg_match('/^php\d*$/D', $extension)) {
            return false;
        }
        return $extension !== '' && !in_array($extension, self::EXECUTABLE_EXTENSIONS, true);
    }

    /**
     * 扫描明确脚本标签；遇到 PHP 短标签时按完整文件校验语法，不执行内容.
     */
    public static function isImageSafe(string $filename): bool
    {
        if (!is_file($filename) || !is_readable($filename)) {
            return false;
        }
        if (!is_resource($stream = @fopen($filename, 'rb'))) {
            return false;
        }
        $carry = '';
        $hasShortTag = false;
        try {
            while (!feof($stream)) {
                $chunk = fread($stream, 8192);
                if ($chunk === false) {
                    return false;
                }
                $buffer = $carry . $chunk;
                if (self::containsScript($buffer)) {
                    return false;
                }
                $hasShortTag = $hasShortTag || strpos($buffer, '<?') !== false || strpos($buffer, '<%') !== false;
                $carry = substr($buffer, -4096);
            }
            if (!$hasShortTag) {
                return true;
            }
            // 两字节标签也会自然出现在压缩数据中；不能按片段长度放行。
            if (!rewind($stream)) {
                return false;
            }
            $content = stream_get_contents($stream);
            // 完整复核超出流式重叠区的文本标签。
            if ($content === false || self::containsScript($content)) {
                return false;
            }
            if (strpos($content, '<?') === false) {
                return true;
            }
            // 未安装 tokenizer 时保留保守拒绝行为，解析过程不会执行上传内容。
            if (!function_exists('token_get_all')) {
                return false;
            }
            // 分别覆盖 short_open_tag 关闭和开启；前面的随机 <? 不能掩盖后面的 <?=。
            if (strpos($content, '<?=') !== false && self::isPhpProgram(strtr($content, ['<?=' => '<?php echo ', '<?' => '<!']))) {
                return false;
            }
            return !self::isPhpProgram(strtr($content, ['<?=' => '<?php echo ', '<?' => '<?php ']));
        } finally {
            fclose($stream);
        }
    }

    /**
     * Backward-compatible path validation for older ThinkLibrary releases.
     */
    private static function isPathSafe(string $name): bool
    {
        if (method_exists(Storage::class, 'isPathSafe')) {
            return Storage::isPathSafe($name);
        }
        if ($name === '' || strlen($name) > 1024 || preg_match('/[\x00-\x1F\x7F%?#\\\\\\\]/', $name)) {
            return false;
        }
        return preg_match('#^(?:[A-Za-z0-9_-]+/)*[A-Za-z0-9_-]+\.[A-Za-z0-9]+$#D', $name) === 1;
    }

    /**
     * 检查服务端脚本起始标签.
     */
    private static function containsScript(string $content): bool
    {
        // 明确的起始标签始终拦截；旧式 ASP 标签需要有完整的文本代码上下文。
        $matched = preg_match('/<\?php|<script|<%[\x09\x0A\x0C\x0D\x20-\x7E]*%>/i', $content);
        return $matched === false || $matched > 0;
    }

    /**
     * 仅解析语法，不执行文件；压缩二进制中的偶然标签通常不能构成完整 PHP 程序.
     */
    private static function isPhpProgram(string $content): bool
    {
        try {
            token_get_all($content, TOKEN_PARSE);
            return true;
        } catch (\ParseError $error) {
            return false;
        }
    }
}
