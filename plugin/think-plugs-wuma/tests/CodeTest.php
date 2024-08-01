<?php

namespace plugin\tests;

use PHPUnit\Framework\TestCase;
use plugin\wuma\service\CodeService;

class CodeTest extends TestCase
{
    public function testCode()
    {
        // 10063 807019146688 112NU23VW81R
        // 100000034 510144829202 6IC7V325CG2C
        // 100000033 510245492492 6IE7I3ZPBQKC
        $min = '100000034';
        $num = '510144829202';
        $enc = '6IC7V325CG2C';
        $this->assertEquals(CodeService::num2min($num), $min, "NUM 解码失败");
        $this->assertEquals(CodeService::enc2min($enc), $min, "ENC 解码失败");
    }
}