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
     * 流式检查图片中是否夹带服务端脚本.
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
                $carry = substr($buffer, -4096);
            }
            return true;
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
     * @param string $content 待检查的二进制内容
     * @return bool 存在脚本标签时返回 true
     */
    private static function containsScript(string $content): bool
    {
        // 强特征（>= 5 字节），正常二进制数据中命中概率约 1/2^40，可全文匹配
        $strongPattern = '#<\?php|<script|<!--\#(?:include|exec)#i';
        // 弱特征（2 字节），只在较长的可打印片段中匹配，排除 XML/XMP 指令
        $weakPattern = '#<\?(?!xml|xpacket|x:)|<%#i';
        // 短特征（3 字节，如 `<?=`、`<%=`），命中概率约 1/2^24，较短片段也能匹配
        $shortPattern = '#<\?=|<%[@=]|<%--#i';
        // 连续可打印文本片段，脚本代码必然落在这样的片段中
        $fragmentPattern = '/[\x09\x0A\x0C\x0D\x20-\x7E]{12,}/';
        // 片段达到该长度时才使用 2 字节的弱特征，更短的片段只用 3 字节特征
        $weakMinLength = 16;

        $strong = preg_match($strongPattern, $content);
        if ($strong === false || $strong > 0) {
            return true;
        }
        $count = preg_match_all($fragmentPattern, $content, $matches);
        if ($count === false) {
            return true;
        }
        foreach ($matches[0] as $fragment) {
            $pattern = strlen($fragment) >= $weakMinLength ? $weakPattern : $shortPattern;
            if (preg_match($pattern, $fragment) > 0) {
                return true;
            }
        }
        return false;
    }
}
