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

namespace plugin\system\service;

use plugin\system\model\SystemUser;
use think\admin\helper\FormBuilder;
use think\admin\Service;

class UserService extends Service
{
    private const PASSWORD_PATTERN = '^(?![\d]+$)(?![a-zA-Z]+$)(?![^\da-zA-Z]+$).{6,32}$';

    /**
     * 生成系统用户密码哈希。
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * 校验系统用户登录密码。
     */
    public static function verifyPassword(string $password, ?string $hash): bool
    {
        $hash = trim((string)$hash);
        return $hash !== '' && password_verify($password, $hash);
    }

    public static function buildPassForm(bool $withOldPassword = false): FormBuilder
    {
        $builder = FormBuilder::mk()
            ->addTextInput('username', '登录用户账号', 'Username', false, '登录用户账号创建后，不允许再次修改。', null, [
                'readonly' => null,
                'class' => 'think-bg-gray',
            ]);

        if ($withOldPassword) {
            $builder->addPassInput('oldpassword', '当前登录密码', 'Current Password', true, '请先输入当前登录密码完成验证。', null, [
                'maxlength' => 32,
                'required-error' => '旧的密码不能为空！',
            ]);
        }

        return $builder
            ->addPassInput('password', '新的登录密码', 'New Password', true, '密码必须包含大小写字母、数字、符号的任意两者组合。', self::PASSWORD_PATTERN, [
                'maxlength' => 32,
                'required-error' => '登录密码不能为空！',
                'pattern-error' => '登录密码格式错误！',
            ])
            ->addPassInput('repassword', '重复登录密码', 'Repeat Password', true, '密码必须包含大小写字母、数字、符号的任意两者组合。', self::PASSWORD_PATTERN, [
                'maxlength' => 32,
                'required-error' => '重复密码不能为空！',
                'pattern-error' => '重复密码格式错误！',
            ])
            ->addValidateRule('repassword', 'confirm:password', '两次输入的密码不一致！')
            ->addSubmitButton()
            ->addCancelButton();
    }

    public static function loadPassUser(int $id): array
    {
        if ($id < 1) {
            return [];
        }

        $user = SystemUser::mk()->findOrEmpty($id);
        return $user->isEmpty() ? [] : $user->toArray();
    }
}
