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

namespace app\admin\tests;

use app\admin\service\UploadSecurity;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class UploadSecurityTest extends TestCase
{
    public function testRejectsFragmentPathUsedToWritePhpBelowAllowedExtension(): void
    {
        $this->assertFalse(UploadSecurity::isNameSafe(
            '2c/970ac53304323a625807f97211acee1.shtml#/public/1234.php',
            'shtml'
        ));
    }

    /**
     * @dataProvider unsafeNameProvider
     */
    public function testRejectsUnsafeStorageNames(string $name): void
    {
        $this->assertFalse(UploadSecurity::isNameSafe($name, 'jpg'));
    }

    public static function unsafeNameProvider(): array
    {
        return [
            'query path' => ['ab/hash.jpg?/public/example.php'],
            'encoded fragment' => ['ab/hash.jpg%23/public/example.php'],
            'encoded slash' => ['ab/hash.jpg%2fpublic%2fexample.php'],
            'parent traversal' => ['ab/../hash.jpg'],
            'absolute path' => ['/ab/hash.jpg'],
            'backslash' => ['ab\hash.jpg'],
            'hidden config' => ['ab/.user.ini'],
        ];
    }

    /**
     * @dataProvider executableExtensionProvider
     */
    public function testRejectsExecutableExtensions(string $extension): void
    {
        $this->assertFalse(UploadSecurity::isExtensionSafe($extension));
    }

    public static function executableExtensionProvider(): array
    {
        return [
            ['php'], ['php8'], ['php9'], ['php10'], ['phtml'], ['pht'], ['phar'], ['shtml'], ['cgi'], ['sh'], ['exe'], ['so'],
        ];
    }

    public function testScansTheMiddleOfAnImageForPhpPayloads(): void
    {
        $filename = tempnam(sys_get_temp_dir(), 'upload-security-');
        $this->assertNotFalse($filename);
        // Harmless PHP constants are sufficient to exercise script tag detection.
        file_put_contents($filename, 'GIF89a' . str_repeat("\0", 2048) . '<?php echo 1;?>');

        try {
            $this->assertFalse(UploadSecurity::isImageSafe($filename));
        } finally {
            unlink($filename);
        }
    }

    public function testRejectsShortEchoTagWithoutClosingTag(): void
    {
        // 片段需达到 12 个可打印字节才参与检测，这里使用真实长度的短标签 payload。
        $filename = $this->writeImagePayload('<?= system($_GET[0]);');

        try {
            $this->assertFalse(UploadSecurity::isImageSafe($filename));
        } finally {
            unlink($filename);
        }
    }

    /**
     * 已知限制：不足 12 个可打印字节的脚本片段会被放行.
     */
    public function testAllowsScriptFragmentShorterThanTwelvePrintableBytes(): void
    {
        $filename = $this->writeImagePayload('<?=1;');

        try {
            $this->assertTrue(UploadSecurity::isImageSafe($filename));
        } finally {
            unlink($filename);
        }
    }

    public function testRejectsShortTagAfterMoreThanTwoKilobytes(): void
    {
        $filename = $this->writeImagePayload('<?' . str_repeat(' ', 4096) . 'echo 1;?>');

        try {
            $this->assertFalse(UploadSecurity::isImageSafe($filename));
        } finally {
            unlink($filename);
        }
    }

    public function testAllowsGeneratedImageStorageNameAndBinaryData(): void
    {
        $filename = tempnam(sys_get_temp_dir(), 'upload-security-');
        $this->assertNotFalse($filename);
        file_put_contents($filename, "GIF89a\0\1\2\3\4\5");

        try {
            $this->assertTrue(UploadSecurity::isNameSafe('image/12/34567890abcdef.jpg', 'jpg'));
            $this->assertTrue(UploadSecurity::isExtensionSafe('jpg'));
            $this->assertTrue(UploadSecurity::isImageSafe($filename));
        } finally {
            unlink($filename);
        }
    }

    /**
     * 随机二进制（模拟图片熵编码数据）不得被误判为夹带脚本.
     */
    public function testAllowsRandomBinaryImageData(): void
    {
        $filename = tempnam(sys_get_temp_dir(), 'upload-security-');
        $this->assertNotFalse($filename);

        try {
            for ($round = 0; $round < 20; ++$round) {
                file_put_contents($filename, self::randomBinary(1048576, $round));
                $this->assertTrue(
                    UploadSecurity::isImageSafe($filename),
                    sprintf('1MB 随机二进制样本 #%d 被误判为夹带脚本。', $round)
                );
            }
        } finally {
            @unlink($filename);
        }
    }

    /**
     * 合法元数据（JPEG XMP / XML 声明 / EXIF 描述 / PNG tEXt）必须放行.
     * @dataProvider benignMetadataProvider
     */
    public function testAllowsBenignImageMetadata(string $content): void
    {
        $filename = tempnam(sys_get_temp_dir(), 'upload-security-');
        $this->assertNotFalse($filename);
        file_put_contents($filename, $content);

        try {
            $this->assertTrue(UploadSecurity::isImageSafe($filename), '合法元数据被误判为夹带脚本。');
        } finally {
            @unlink($filename);
        }
    }

    public static function benignMetadataProvider(): array
    {
        $xmp = '<?xpacket begin="" id="W5M0MpCehiHzreSzNTczkc9d"?>'
            . "\n" . '<x:xmpmeta xmlns:x="adobe:ns:meta/" x:xmptk="Image::ExifTool 12.40">'
            . "\n" . '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">'
            . '<rdf:Description rdf:about="" xmlns:dc="http://purl.org/dc/elements/1.1/"><dc:format>image/jpeg</dc:format></rdf:Description>'
            . '</rdf:RDF>' . "\n" . '</x:xmpmeta>' . "\n" . '<?xpacket end="w"?>';

        return [
            'jpeg xmp packet' => ["\xFF\xD8\xFF\xE1" . str_repeat("\0", 64) . $xmp],
            'xmp with xml declaration' => ["<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" . $xmp],
            'exif description' => ["Exif\x00\x00" . str_repeat("\x01", 32) . 'Copyright (c) 2026 ThinkAdmin Studio. All rights reserved.'],
            'png text chunk' => ["\x89PNG\r\n\x1A\n" . str_repeat("\x80", 32) . "tEXtComment\x00Created with ThinkAdmin image uploader"],
            'plain ascii' => ["plain text without any server side script tag\n"],
        ];
    }

    /**
     * 夹带的服务端脚本必须被拦截.
     * @dataProvider scriptPayloadProvider
     */
    public function testRejectsEmbeddedScriptPayloads(string $payload): void
    {
        $filename = $this->writeImagePayload("\n" . $payload . "\n");

        try {
            $this->assertFalse(UploadSecurity::isImageSafe($filename), sprintf('脚本片段 [%s] 未被拦截。', $payload));
        } finally {
            @unlink($filename);
        }
    }

    public static function scriptPayloadProvider(): array
    {
        return [
            'php tag' => ['<?php system($_GET[0]); ?>'],
            'php tag uppercase' => ['<?PHP phpinfo();?>'],
            'javascript' => ['<script>alert(1)</script>'],
            'short echo tag' => ['<?= system($_GET[0]) ?>'],
            'short echo minimal' => ['<?=$_GET[0]?>'],
            'asp' => ['<% eval request("x") %>'],
            'jsp directive' => ['<%@ page import="java.io.*"%>'],
            'ssi include' => ['<!--#include file="/etc/passwd"-->'],
            'ssi exec' => ['<!--#exec cmd="id"-->'],
            'short tag then newline' => ["<?\nphpinfo();\n?>"],
        ];
    }

    /**
     * 生成确定性、均匀分布的随机二进制（用哈希流，比逐字节 mt_rand 快一个数量级）.
     */
    private static function randomBinary(int $length, int $seed): string
    {
        $binary = '';
        for ($index = 0; strlen($binary) < $length; ++$index) {
            $binary .= md5("upload-security:{$seed}:{$index}", true);
        }
        return substr($binary, 0, $length);
    }

    private function writeImagePayload(string $payload): string
    {
        $filename = tempnam(sys_get_temp_dir(), 'upload-security-');
        $this->assertNotFalse($filename);
        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==');
        file_put_contents($filename, $gif . str_repeat("\0", 2048) . $payload);
        $this->assertNotFalse(getimagesize($filename));
        return $filename;
    }
}
