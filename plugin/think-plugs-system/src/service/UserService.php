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

use plugin\system\model\SystemAuth;
use plugin\system\model\SystemBase;
use plugin\system\model\SystemUser;
use think\admin\Exception;
use think\admin\helper\QueryHelper;
use think\admin\Service;

/**
 * 系统后台用户：密码哈希、校验与账户字段处理。
 * @class UserService
 */
class UserService extends Service
{
    /**
     * 构建用户列表上下文.
     * @return array<string, mixed>
     */
    public static function buildIndexContext(): array
    {
        $type = self::normalizeIndexType(strval(request()->get('type', 'index')));
        return [
            'title' => '系统用户管理',
            'type' => $type,
            'bases' => SystemBase::items('身份权限'),
            'requestBaseUrl' => request()->baseUrl(),
        ];
    }

    /**
     * 构建用户表单上下文.
     * @return array<string, mixed>
     */
    public static function buildFormContext(string $action): array
    {
        $id = intval(request()->param('id', 0));
        $bases = SystemBase::itemsWithPlugins('身份权限');
        $auths = SystemAuth::itemsWithPlugins();

        return [
            'action' => $action,
            'id' => $id,
            'isEdit' => $action === 'edit' || $id > 0,
            'actionUrl' => url($action, array_filter(['id' => $id ?: null]))->build(),
            'bases' => $bases,
            'baseGroups' => self::buildPluginGroups($bases),
            'auths' => $auths,
            'authGroups' => self::buildPluginGroups($auths),
            'super' => AuthService::getSuperName(),
        ];
    }

    /**
     * 构建当前用户资料表单上下文.
     * @return array<string, mixed>
     */
    public static function buildInfoContext(int $id): array
    {
        return [
            'action' => 'info',
            'id' => $id,
            'isEdit' => true,
            'actionUrl' => url('info', array_filter(['id' => $id ?: null]))->build(),
            'bases' => [],
            'baseGroups' => [],
            'auths' => [],
            'authGroups' => [],
            'super' => AuthService::getSuperName(),
        ];
    }

    /**
     * 规范化用户列表类型.
     */
    public static function normalizeIndexType(string $type): string
    {
        return strtolower(trim($type)) === 'index' ? 'index' : 'recycle';
    }

    /**
     * 应用用户列表查询.
     * @param array<string, mixed> $context
     */
    public static function applyIndexQuery(QueryHelper $query, array $context = []): void
    {
        $type = self::normalizeIndexType(strval($context['type'] ?? request()->get('type', 'index')));
        $query->where(['status' => intval($type === 'index')]);
        $query->with(['userinfo' => static function ($query) {
            $query->field('code,name,content');
        }]);
        $query->equal('status,usertype')->dateBetween('login_at,create_time');
        $query->like('username|nickname#username,contact_phone#phone,contact_mail#mail');
    }

    /**
     * 加载用户表单数据.
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     * @throws Exception
     */
    public static function loadFormData(array $context): array
    {
        $id = intval($context['id'] ?? 0);
        if ($id < 1) {
            return ['authorize' => []];
        }

        $user = SystemUser::mk()->findOrEmpty($id);
        if ($user->isEmpty()) {
            throw new Exception('用户数据不存在！');
        }

        $data = $user->toArray();
        $data['authorize'] = self::normalizeAuthorize($data['authorize'] ?? []);
        return $data;
    }

    /**
     * 整理用户表单数据.
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     * @throws Exception
     */
    public static function prepareFormData(array $data, array $context): array
    {
        $id = intval($context['id'] ?? 0);
        $item = $id > 0 ? SystemUser::mk()->findOrEmpty($id) : SystemUser::mk();
        if ($id > 0 && $item->isEmpty()) {
            throw new Exception('用户数据不存在！');
        }

        $username = trim(strval($data['username'] ?? ''));
        if ($id > 0) {
            $username = strval($item->getAttr('username'));
        }
        if ($username === '') {
            throw new Exception('登录账号不能为空！');
        }

        $authorize = self::normalizeAuthorize(request()->post('authorize', []));
        if ($username !== AuthService::getSuperName() && count($authorize) < 1) {
            throw new Exception('未配置权限！');
        }

        $status = intval(request()->post('status', $id > 0 ? $item->getAttr('status') : 1));
        if (!in_array($status, [0, 1], true)) {
            throw new Exception('状态值范围异常！');
        }

        return [
            'id' => $id,
            'username' => $username,
            'nickname' => trim(strval($data['nickname'] ?? '')),
            'headimg' => trim(strval($data['headimg'] ?? '')),
            'usertype' => trim(strval(request()->post('usertype', ''))),
            'authorize' => arr2str($authorize),
            'contact_qq' => trim(strval($data['contact_qq'] ?? '')),
            'contact_mail' => trim(strval($data['contact_mail'] ?? '')),
            'contact_phone' => trim(strval($data['contact_phone'] ?? '')),
            'describe' => trim(strval($data['describe'] ?? '')),
            'sort' => intval(request()->post('sort', $id > 0 ? $item->getAttr('sort') : 0)),
            'status' => $status,
        ];
    }

    /**
     * 整理当前用户资料表单数据.
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     * @throws Exception
     */
    public static function prepareInfoData(array $data, array $context): array
    {
        $id = intval($context['id'] ?? 0);
        $item = $id > 0 ? SystemUser::mk()->findOrEmpty($id) : SystemUser::mk();
        if ($id < 1 || $item->isEmpty()) {
            throw new Exception('用户数据不存在！');
        }

        return [
            'id' => $id,
            'username' => strval($item->getAttr('username')),
            'nickname' => trim(strval($data['nickname'] ?? '')),
            'headimg' => trim(strval($data['headimg'] ?? '')),
            'usertype' => strval($item->getAttr('usertype')),
            'authorize' => strval($item->getAttr('authorize')),
            'contact_qq' => trim(strval($data['contact_qq'] ?? '')),
            'contact_mail' => trim(strval($data['contact_mail'] ?? '')),
            'contact_phone' => trim(strval($data['contact_phone'] ?? '')),
            'describe' => trim(strval($data['describe'] ?? '')),
            'sort' => intval($item->getAttr('sort')),
            'status' => intval($item->getAttr('status')),
        ];
    }

    /**
     * 保存用户表单数据.
     * @param array<string, mixed> $data
     * @throws Exception
     */
    public static function saveFormData(array $data): void
    {
        $id = intval($data['id'] ?? 0);
        $item = $id > 0 ? SystemUser::mk()->findOrEmpty($id) : SystemUser::mk();
        if ($id > 0 && $item->isEmpty()) {
            throw new Exception('用户数据不存在！');
        }
        if ($id < 1 && SystemUser::mk()->where(['username' => strval($data['username'] ?? '')])->count() > 0) {
            throw new Exception('账号已经存在，请使用其它账号！');
        }

        $payload = [
            'usertype' => strval($data['usertype'] ?? ''),
            'nickname' => strval($data['nickname'] ?? ''),
            'headimg' => strval($data['headimg'] ?? ''),
            'authorize' => strval($data['authorize'] ?? ''),
            'contact_qq' => strval($data['contact_qq'] ?? ''),
            'contact_mail' => strval($data['contact_mail'] ?? ''),
            'contact_phone' => strval($data['contact_phone'] ?? ''),
            'describe' => strval($data['describe'] ?? ''),
            'sort' => intval($data['sort'] ?? 0),
            'status' => intval($data['status'] ?? 1),
        ];
        if ($id < 1) {
            $payload['username'] = strval($data['username'] ?? '');
            $payload['password'] = self::hashPassword(strval($data['username'] ?? ''));
        }

        if ($item->save($payload) === false) {
            throw new Exception('数据保存失败，请稍候再试！');
        }
    }

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

    public static function loadPassUser(int $id): array
    {
        if ($id < 1) {
            return [];
        }

        $user = SystemUser::mk()->findOrEmpty($id);
        return $user->isEmpty() ? [] : $user->toArray();
    }

    /**
     * 构建插件分组.
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private static function buildPluginGroups(array $items): array
    {
        $groups = [];
        foreach ($items as $item) {
            $code = strval($item['plugin_group'] ?? 'common');
            if (!isset($groups[$code])) {
                $groups[$code] = [
                    'code' => $code,
                    'name' => strval($item['plugin_title'] ?? $code),
                    'items' => [],
                ];
            }
            $groups[$code]['items'][] = $item;
        }

        $specials = [];
        foreach (['common', 'mixed'] as $code) {
            if (isset($groups[$code])) {
                $specials[$code] = $groups[$code];
                unset($groups[$code]);
            }
        }

        uasort($groups, static function (array $a, array $b): int {
            return strcmp(strval($a['name'] ?? ''), strval($b['name'] ?? ''));
        });

        return array_values(array_merge($groups, $specials));
    }

    /**
     * 规范授权数据.
     * @param mixed $authorize
     * @return array<int, string>
     */
    private static function normalizeAuthorize(mixed $authorize): array
    {
        $result = [];
        foreach (is_array($authorize) ? $authorize : str2arr(strval($authorize)) as $item) {
            $value = trim(strval($item));
            if ($value !== '' && !in_array($value, $result, true)) {
                $result[] = $value;
            }
        }
        return $result;
    }
}
