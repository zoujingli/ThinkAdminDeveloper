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
            'query path' => ['ab/hash.jpg?/public/shell.php'],
            'encoded fragment' => ['ab/hash.jpg%23/public/shell.php'],
            'encoded slash' => ['ab/hash.jpg%2fpublic%2fshell.php'],
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
        file_put_contents($filename, 'GIF89a' . str_repeat("\0", 2048) . '<?php eval(base64_decode($_REQUEST[\'cmd\']));?>');

        try {
            $this->assertFalse(UploadSecurity::isImageSafe($filename));
        } finally {
            unlink($filename);
        }
    }

    public function testRejectsShortEchoTagWithoutClosingTag(): void
    {
        $filename = $this->writeImagePayload('<?=system($_GET[\'x\']);');

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
