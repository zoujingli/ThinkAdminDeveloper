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

namespace plugin\system\tests;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class ConsoleCssUtilityTest extends TestCase
{
    public function testSpacingBorderAndRadiusUtilitiesExistInLessSourceAndCompiledCss(): void
    {
        $lessFile = TEST_PROJECT_ROOT . '/public/static/theme/css/_custom.less';
        $cssFile = TEST_PROJECT_ROOT . '/public/static/theme/css/console.css';

        $this->assertFileExists($lessFile);
        $this->assertFileExists($cssFile);

        $less = file_get_contents($lessFile) ?: '';
        $css = file_get_contents($cssFile) ?: '';

        $this->assertStringContainsString('// ma/mt/mr/mb/ml/mx/my', $less);
        $this->assertStringContainsString('// pa/pt/pr/pb/pl/px/py', $less);
        $this->assertStringContainsString('// ba/bt/br/bb/bl/b0', $less);
        $this->assertStringContainsString('.@{base}x@{n} {', $less);
        $this->assertStringContainsString('@{prop}-left: @n * 1px !important;', $less);
        $this->assertStringContainsString('@{prop}-right: @n * 1px !important;', $less);
        $this->assertStringContainsString('.@{base}y@{n} {', $less);
        $this->assertStringContainsString('@{prop}-top: @n * 1px !important;', $less);
        $this->assertStringContainsString('@{prop}-bottom: @n * 1px !important;', $less);
        $this->assertStringContainsString('.b0 {', $less);
        $this->assertStringContainsString('.ba {', $less);
        $this->assertStringContainsString('.bt0 {', $less);
        $this->assertStringContainsString('.border-radius {', $less);
        $this->assertStringContainsString('&-@{value} {', $less);

        $this->assertStringContainsString('.mx10{margin-left:10px!important;margin-right:10px!important}', $css);
        $this->assertStringContainsString('.my10{margin-top:10px!important;margin-bottom:10px!important}', $css);
        $this->assertStringContainsString('.px10{padding-left:10px!important;padding-right:10px!important}', $css);
        $this->assertStringContainsString('.py10{padding-top:10px!important;padding-bottom:10px!important}', $css);
        $this->assertStringContainsString('.b0{border:0!important}', $css);
        $this->assertStringContainsString('.ba{border:1px solid #eee}', $css);
        $this->assertStringContainsString('.bt0{border-top:0!important}', $css);
        $this->assertStringContainsString('.br0{border-right:0!important}', $css);
        $this->assertStringContainsString('.bb0{border-bottom:0!important}', $css);
        $this->assertStringContainsString('.bl0{border-left:0!important}', $css);
        $this->assertStringContainsString('.border-radius{border-radius:50%!important}', $css);
        $this->assertStringContainsString('.border-radius-4{border-radius:4px!important}', $css);
    }
}
