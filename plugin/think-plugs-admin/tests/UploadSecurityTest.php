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
        $filename = $this->writeImagePayload('<?=1;');

        try {
            $this->assertFalse(UploadSecurity::isImageSafe($filename));
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

    public function testAllowsCompressedJpegContainingIncidentalShortTags(): void
    {
        $image = imagecreatetruecolor(512, 512);
        for ($y = 0; $y < 512; ++$y) {
            for ($x = 0; $x < 512; ++$x) {
                imagesetpixel($image, $x, $y, hexdec(substr(md5("{$x}:{$y}"), 0, 6)));
            }
        }
        ob_start();
        imagejpeg($image, null, 92);
        $jpeg = ob_get_clean();
        imagedestroy($image);
        $this->assertStringContainsString('<?', $jpeg);
        $this->assertStringContainsString('<%', $jpeg);
        $filename = tempnam(sys_get_temp_dir(), 'upload-security-');

        try {
            file_put_contents($filename, $jpeg);
            $this->assertNotFalse(getimagesize($filename));
            $this->assertTrue(UploadSecurity::isImageSafe($filename));
        } finally {
            unlink($filename);
        }
    }

    public function testAllowsXmpProcessingInstructions(): void
    {
        $filename = $this->writeImagePayload(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<?xpacket begin="" id="W5M0MpCehiHzreSzNTczkc9d"?>'
            . '<x:xmpmeta xmlns:x="adobe:ns:meta/"></x:xmpmeta><?xpacket end="w"?>'
        );

        try {
            $this->assertTrue(UploadSecurity::isImageSafe($filename));
        } finally {
            unlink($filename);
        }
    }

    public function testAllowsIncidentalShortEchoBytesInBinaryImageData(): void
    {
        $filename = $this->writeImagePayload("\xFF<?=\x00\x83<%=\x00");

        try {
            $this->assertTrue(UploadSecurity::isImageSafe($filename));
        } finally {
            unlink($filename);
        }
    }

    /**
     * @dataProvider embeddedScriptProvider
     */
    public function testRejectsScriptsWithoutAFragmentLengthExemption(string $payload): void
    {
        $filename = $this->writeImagePayload($payload);

        try {
            $this->assertFalse(UploadSecurity::isImageSafe($filename));
        } finally {
            unlink($filename);
        }
    }

    public static function embeddedScriptProvider(): array
    {
        return [
            'short echo' => ['<?=7*7;'],
            'short open tag' => ['<? echo 1; ?>'],
            'x label' => ['<?x: echo 1; /* metadata is not a PHP label exemption */ ?>'],
            'xml label' => ['<?xml: echo 1; ?>'],
            'xpacket label' => ['<?xpacket: echo 1; ?>'],
            'short tag without closing tag' => ['<?echo 1;'],
            'short tag across multiple chunks' => ['<?' . str_repeat(' ', 20000) . 'echo 1; ?>'],
            'full tag after binary noise' => ["<?\xFF\x00" . '<?php echo 1; ?>'],
            'short echo after invalid short open tag' => ["<?\xFF\x00" . '<?=1;'],
            'short echo after XMP' => ['<?xpacket begin=""?><?=1;'],
            'short echo across chunk boundary' => [str_repeat("\0", 6109) . '<?=1;'],
            'asp' => ['<%echo 1;%>'],
            'asp across multiple chunks' => ['<%' . str_repeat(' ', 20000) . 'echo 1;%>'],
            'script element' => ['<script>alert(1)</script>'],
        ];
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
