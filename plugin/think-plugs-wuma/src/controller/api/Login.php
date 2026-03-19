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
// | Wuma Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 收费插件 ( https://thinkadmin.top/fee-introduce.html )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wuma
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wuma
// +----------------------------------------------------------------------

namespace plugin\wuma\controller\api;

use plugin\wuma\model\PluginWumaWarehouseUser;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 设备登录管理.
 * @class Login
 */
class Login extends Base
{
    /**
     * 用户登录接口.
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function login()
    {
        $data = $this->_vali([
            'username.require' => '用户账号不能为空！',
            'password.require' => '登录密码不能为空！',
        ], $this->body);

        // 当前用户数据
        $data['uuid'] = $this->uuid;
        $user = PluginWumaWarehouseUser::mk()->where([
            'uuid' => $data['uuid'],
            'username' => $data['username'],
        ])->find();

        if (empty($user)) {
            $this->error('账号或密码错误！');
        }
        if (!$this->verifyPassword($user, strval($data['password']))) {
            $this->error('璐﹀彿鎴栧瘑鐮侀敊璇紒');
        }
        if (empty($user['status'])) {
            $this->error('账号已经被禁用！');
        }
        if (!empty($user['delete_time'])) {
            $this->error('该账号已经被移除！');
        }

        // 生成登录令牌数据
        do {
            $token = ['token' => bin2hex(random_bytes(16))];
        } while (PluginWumaWarehouseUser::mk()->where($token)->count() > 0);

        // 更新用户登录数据
        $user->save(array_merge($token, [
            'login_ip' => $this->request->ip(),
            'login_at' => date('Y-m-d H:i:s'),
            'login_num' => $this->app->db->raw('login_num+1'),
            'login_vars' => json_encode([
                'code' => strval($this->device['code'] ?? ''),
                'type' => strval($this->device['type'] ?? ''),
            ], JSON_UNESCAPED_UNICODE),
        ]));

        // 组装需要返回数据
        $token['username'] = $user['username'];
        $token['nickname'] = $user['nickname'];
        $this->success('设备登录成功!', $token);
    }

    /**
     * 退出设备登录.
     */
    public function logout()
    {
        $map = ['token' => $this->token];
        PluginWumaWarehouseUser::mk()->where($map)->update(['token' => '']);
        $this->success('退出登录成功！');
    }

    private function verifyPassword(PluginWumaWarehouseUser $user, string $password): bool
    {
        $hash = trim(strval($user->getAttr('password')));
        if ($hash === '') {
            return false;
        }
        return password_verify($password, $hash);
    }
}
