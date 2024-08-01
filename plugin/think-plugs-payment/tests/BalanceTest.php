<?php

namespace think\admin\tests;

use PHPUnit\Framework\TestCase;
use plugin\account\service\Account;
use plugin\payment\service\Balance;

class BalanceTest extends TestCase
{
    public function testBindAccount()
    {
        $username = 'UserName' . uniqid();
        $account = Account::mk(Account::WAP);
        $account->set(['phone' => '138888888888']);

        // 关联绑定主账号
        $info = $account->bind(['phone' => '138888888888'], ['username' => $username]);
        $this->assertEquals($info['user']['username'], $username, '账号绑定关联成功！');
    }

    public function testCreateAmount()
    {
        $code = uniqid('test');
        $amount = rand(100, 5000) / 100;
        $info = Balance::create(1, $code, '充值测试', $amount, '来自充值案例测试！');
        $this->assertTrue($info->isExists(), '充值成功测试！');
    }

    public function testUnlockAmount()
    {
        $code = uniqid('test');
        $amount = rand(100, 5000) / 100;
        $info = Balance::create(1, $code, '充值测试', $amount, '来自充值案例测试，用于解锁！');
        $this->assertTrue($info->isExists(), '充值成功测试！');

        $info = Balance::unlock($code);
        $this->assertEquals($info->getAttr('unlock'), 1, '解锁成功测试！');
    }

    public function testCancelAmount()
    {
        $code = uniqid('test');
        $amount = rand(100, 5000) / 100;
        $info = Balance::create(1, $code, '充值测试', $amount, '来自充值案例测试，用于取消！');
        $this->assertTrue($info->isExists(), '充值成功测试！');

        $info = Balance::cancel($code);
        $this->assertEquals($info->getAttr('cancel'), 1, '取消成功测试！');
    }
}