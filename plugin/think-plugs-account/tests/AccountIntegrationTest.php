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

namespace think\admin\tests;

use plugin\account\model\PluginAccountAuth;
use plugin\account\model\PluginAccountBind;
use plugin\account\model\PluginAccountUser;
use plugin\account\service\Account;
use think\admin\tests\Support\SqliteIntegrationTestCase;

/**
 * @internal
 * @coversNothing
 */
class AccountIntegrationTest extends SqliteIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->configureAccountAccess();
    }

    public function testSetCreatesBindAndAuthRecords(): void
    {
        $phone = '1380013' . random_int(1000, 9999);
        $info = Account::mk(Account::WAP)->set([
            'phone' => $phone,
            'extra' => ['source' => 'integration'],
        ], true);

        $this->assertSame($phone, $info['phone']);
        $this->assertSame(Account::WAP, $info['type']);
        $this->assertSame('https://example.com/default-account.png', $info['headimg']);
        $this->assertNotEmpty($info['nickname']);
        $this->assertNotEmpty($info['token']);
        $this->assertArrayNotHasKey('id', $info['user']);

        $bind = PluginAccountBind::mk()->where(['phone' => $phone])->findOrEmpty();
        $this->assertTrue($bind->isExists());
        $this->assertSame(['source' => 'integration'], $bind->getAttr('extra'));

        $auth = PluginAccountAuth::mk()->where([
            'usid' => $bind->getAttr('id'),
            'type' => Account::WAP,
        ])->findOrEmpty();
        $this->assertTrue($auth->isExists());
        $this->assertGreaterThan(time(), intval($auth->getAttr('time')));
    }

    public function testBindRecodeAndUnbindUpdateUserRelations(): void
    {
        $phone = $this->randomPhone('1390013');
        $account = $this->createAccountFixture(Account::WAP, ['phone' => $phone]);

        $bound = $account->bind(['phone' => $phone], [
            'username' => 'tester-' . random_int(100, 999),
            'extra' => ['scene' => 'integration'],
        ]);

        $this->assertSame($phone, $bound['phone']);
        $this->assertNotEmpty($bound['user']);
        $this->assertSame($phone, $bound['user']['phone']);
        $this->assertSame('integration', $bound['user']['extra']['scene']);
        $this->assertSame('https://example.com/default-account.png', $bound['user']['headimg']);

        $user = PluginAccountUser::mk()->findOrEmpty(intval($bound['user']['id']));
        $oldCode = strval($user->getAttr('code'));
        $recode = $account->recode();
        $this->assertNotSame($oldCode, $recode['user']['code']);

        $account->unBind();
        $fresh = Account::mk(Account::WAP, ['phone' => $phone], false)->get();

        $this->assertSame(0, intval($fresh['unid']));
        $this->assertArrayNotHasKey('id', $fresh['user']);
    }

    public function testPwdModifySynchronizesBindAndUserPasswords(): void
    {
        $phone = $this->randomPhone('1370013');
        $account = $this->createAccountFixture(Account::WAP, ['phone' => $phone]);
        $bound = $account->bind(['phone' => $phone], ['username' => 'pwd-user-' . random_int(100, 999)]);

        $this->assertTrue($account->pwdModify('Secret@123', false));
        $this->assertTrue($account->pwdVerify('Secret@123'));
        $this->assertFalse($account->pwdVerify('invalid-pass'));

        $user = PluginAccountUser::mk()->findOrEmpty(intval($bound['user']['id']));
        $bind = PluginAccountBind::mk()->where(['phone' => $phone])->findOrEmpty();
        $this->assertTrue(password_verify('Secret@123', strval($user->getAttr('password'))));
        $this->assertTrue(password_verify('Secret@123', strval($bind->getAttr('password'))));
    }

    public function testAllBindAndDelBindReflectMultipleClients(): void
    {
        $primaryPhone = $this->randomPhone('1350013');
        $secondaryPhone = $this->randomPhone('1340013');

        $primary = $this->createAccountFixture(Account::WAP, ['phone' => $primaryPhone]);
        $bound = $primary->bind(['phone' => $primaryPhone], ['username' => 'multi-' . random_int(100, 999)]);
        $unid = intval($bound['user']['id']);

        $secondary = $this->createAccountFixture(Account::WEB, ['phone' => $secondaryPhone]);
        $secondary->bind(['id' => $unid]);

        $all = $primary->allBind();
        $phones = array_column($all, 'phone');
        $expected = [$primaryPhone, $secondaryPhone];
        sort($phones);
        sort($expected);

        $this->assertCount(2, $all);
        $this->assertSame($expected, $phones);

        $after = $primary->delBind($secondary->getUsid());
        $this->assertCount(1, $after);
        $this->assertSame($primaryPhone, $after[0]['phone']);

        $detached = PluginAccountBind::mk()->findOrEmpty($secondary->getUsid());
        $this->assertSame(0, intval($detached->getAttr('unid')));
    }

    protected function defineSchema(): void
    {
        $this->createAccountTables();
    }
}
