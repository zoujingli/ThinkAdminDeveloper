<?php

// +----------------------------------------------------------------------
// | Account Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-account
// | github 代码仓库：https://github.com/zoujingli/think-plugs-account
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\account\service\contract;

use plugin\account\model\PluginAccountAuth;
use plugin\account\model\PluginAccountBind;
use plugin\account\model\PluginAccountUser;
use plugin\account\service\Account;
use think\admin\Exception;
use think\admin\extend\CodeExtend;
use think\admin\extend\JwtExtend;
use think\App;

/**
 * 用户账号通用类
 * @class AccountAccess
 * @package plugin\account\service\contract
 */
class AccountAccess implements AccountInterface
{
    /**
     * 当前应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 当前用户对象
     * @var PluginAccountUser
     */
    protected $user;

    /**
     * 当前认证对象
     * @var PluginAccountAuth
     */
    protected $auth;

    /**
     * 当前终端对象
     * @var PluginAccountBind
     */
    protected $bind;

    /**
     * 当前通道类型
     * @var string
     */
    protected $type;

    /**
     * 授权检查字段
     * @var string
     */
    protected $field;

    /**
     * 是否JWT模式
     * @var boolean
     */
    protected $isjwt;

    /**
     * 令牌有效时间
     * @var integer
     */
    protected $expire = 3600;

    /**
     * 测试专用 TOKEN
     * 主要用于接口文档演示
     * @var string
     */
    public const tester = 'tester';

    /**
     * 通道构造方法
     * @param \think\App $app
     * @param string $type 通道类型
     * @param string $field 授权字段
     * @throws \think\admin\Exception
     */
    public function __construct(App $app, string $type, string $field)
    {
        $this->app = $app;
        $this->type = $type;
        $this->field = $field;
        $this->expire = Account::expire();
    }

    /**
     * 初始化通道
     * @param string|array $token 令牌或条件
     * @param boolean $isjwt 是否返回令牌
     * @return AccountInterface
     * @throws \think\admin\Exception
     * @throws \think\db\exception\DbException
     */
    public function init($token = '', bool $isjwt = true): AccountInterface
    {
        $this->isjwt = $isjwt;
        $this->auth = PluginAccountAuth::mk();
        $this->bind = PluginAccountBind::mk();
        $this->user = PluginAccountUser::mk();
        if (is_string($token)) {
            $map = ['type' => $this->type, 'token' => $token];
            $this->auth = PluginAccountAuth::mk()->where($map)->findOrEmpty();
            $this->bind = $this->auth->client()->findOrEmpty();
            $this->user = $this->bind->user()->findOrEmpty();
        } elseif (is_array($token)) {
            // 返向查询终端账号
            $map = ['deleted' => 0];
            if ($this->type) $map['type'] = $this->type;
            $this->bind = PluginAccountBind::mk()->where($map)->where($token)->findOrEmpty();
            $this->user = $this->bind->user()->findOrEmpty();
            if ($this->bind->isExists()) {
                if (empty($this->type)) $this->type = $this->bind->getAttr('type');
                if ($this->auth->isEmpty()) $this->token(false);
            }
        }
        return $this;
    }

    /**
     * 设置子账号资料
     * @param array $data 用户资料
     * @param boolean $rejwt 返回令牌
     * @return array
     * @throws \think\admin\Exception
     * @throws \think\db\exception\DbException
     */
    public function set(array $data = [], bool $rejwt = false): array
    {
        // 如果传入授权验证字段
        if (isset($data[$this->field])) {
            if ($this->bind->isExists()) {
                if ($data[$this->field] !== $this->bind->getAttr($this->field)) {
                    throw new Exception('禁止强行关联！');
                }
            } else {
                $map = [$this->field => $data[$this->field]];
                if ($this->type) $map['type'] = $this->type;
                $this->bind = PluginAccountBind::mk()->where($map)->findOrEmpty();
            }
        } elseif ($this->bind->isEmpty()) {
            throw new Exception("字段 {$this->field} 为空！");
        }
        $this->bind = $this->save(array_merge($data, ['type' => $this->type]));
        if ($this->bind->isEmpty()) throw new Exception('更新资料失败！');
        // 刷新更新用户模型
        $this->user = $this->bind->user()->findOrEmpty();
        return $this->token()->get($rejwt);
    }

    /**
     * 获取用户数据
     * @param boolean $rejwt 返回令牌
     * @param boolean $refresh 刷新数据
     * @return array
     */
    public function get(bool $rejwt = false, bool $refresh = false): array
    {
        if ($refresh) {
            $this->bind->isExists() && $this->bind->refresh();
            $this->user->isExists() && $this->user->refresh();
        }
        $data = $this->bind->hidden(['sort', 'password'], true)->toArray();
        if ($this->bind->isExists()) {
            if ($this->user->isEmpty()) {
                $this->user = $this->bind->user()->findOrEmpty();
            }
            $data['user'] = $this->user->hidden(['sort', 'password'], true)->toArray();
            if ($rejwt) $data['token'] = $this->isjwt ? JwtExtend::token([
                'type' => $this->auth->getAttr('type'), 'token' => $this->auth->getAttr('token')
            ]) : $this->auth->getAttr('token');
        }
        return $data;
    }

    /**
     * 验证终端密码
     * @param string $pass 待验证密码
     * @return boolean
     * @throws \think\admin\Exception
     */
    public function pwdVerify(string $pass): bool
    {
        $pass = md5($pass);
        if ($this->user->getAttr('password') === $pass) return !!$this->expire();
        return $this->bind->getAttr('password') === $pass && $this->expire();
    }

    /**
     * 修改终端密码
     * @param string $pass 待修改密码
     * @param boolean $event 触发事件
     * @return boolean
     */
    public function pwdModify(string $pass, bool $event = true): bool
    {
        if ($this->bind->isEmpty()) return false;
        $data = ['password' => md5($pass)];
        $this->user->isExists() && $this->user->save($data);
        if (!$this->bind->save($data)) return false;
        if ($event) $this->app->event->trigger('PluginAccountChangePassword', [
            'unid' => $this->getUnid(), 'pass' => $pass
        ]);
        return true;
    }

    /**
     * 绑定主账号
     * @param array $map 主账号条件
     * @param array $data 主账号资料
     * @return array
     * @throws \think\admin\Exception
     */
    public function bind(array $map, array $data = []): array
    {
        if ($this->bind->isEmpty()) throw new Exception('终端账号异常！');
        $this->user = PluginAccountUser::mk()->where(['deleted' => 0])->where($map)->findOrEmpty();
        if (!empty($data['extra'])) $this->user->setAttr('extra', array_merge($this->user->getAttr('extra'), $data['extra']));
        unset($data['id'], $data['code'], $data['extra']);
        // 生成新的用户编号
        if ($this->user->isEmpty()) do $check = ['code' => $data['code'] = $this->userCode()];
        while (PluginAccountUser::mk()->master()->where($check)->findOrEmpty()->isExists());
        // 自动绑定默认头像
        if (empty($data['headimg']) && $this->user->isEmpty() || empty($this->user->getAttr('headimg'))) {
            if (empty($data['headimg'] = $this->bind->getAttr('headimg'))) $data['headimg'] = Account::headimg();
        }
        // 自动生成用户昵称
        if (empty($data['nickname']) && empty($this->user->getAttr('nickname'))) {
            if (empty($data['nickname'] = $this->bind->getAttr('nickname'))) {
                $prefix = Account::config('userPrefix') ?: (Account::get($this->type)['name'] ?? $this->type);
                if ($phone = $data['phone'] ?? $this->user->getAttr('phone')) {
                    $data['nickname'] = $prefix . substr($phone, -4);
                } else {
                    $data['nickname'] = "{$prefix}{$this->bind->getAttr('id')}";
                }
            }
        }
        // 同步用户登录密码
        if (!empty($this->bind->getAttr('password'))) {
            $data['password'] = $this->bind->getAttr('password');
        }
        // 保存更新用户数据
        if ($this->user->save($data + $map)) {
            $this->bind->save(['unid' => $this->user['id']]);
            $this->app->event->trigger('PluginAccountBind', [
                'type' => $this->type,
                'unid' => intval($this->user->getAttr('id')),
                'usid' => intval($this->bind->getAttr('id')),
            ]);
            return $this->get();
        } else {
            throw new Exception('绑定用户失败！');
        }
    }

    /**
     * 解绑主账号
     * @return array
     * @throws \think\admin\Exception
     */
    public function unBind(): array
    {
        if ($this->bind->isEmpty()) {
            throw new Exception('终端账号异常！');
        }
        if (($unid = $this->bind->getAttr('unid')) > 0) {
            $this->bind->save(['unid' => 0]);
            $this->app->event->trigger('PluginAccountUnbind', [
                'type' => $this->type,
                'unid' => intval($unid),
                'usid' => intval($this->bind->getAttr('id')),
            ]);
        }
        return $this->get();
    }

    /**
     * 判断绑定主账号
     * @return boolean
     */
    public function isBind(): bool
    {
        return $this->user->isExists();
    }

    /**
     * 判断是否空账号
     * @return boolean
     */
    public function isNull(): bool
    {
        return $this->bind->isEmpty();
    }

    /**
     * 获取关联终端
     * @return array
     */
    public function allBind(): array
    {
        try {
            if ($this->isNull()) return [];
            if ($this->isBind() && ($unid = $this->bind->getAttr('unid'))) {
                $map = ['unid' => $unid, 'deleted' => 0];
                return PluginAccountBind::mk()->where($map)->select()->toArray();
            } else {
                return [$this->bind->refresh()->toArray()];
            }
        } catch (\Exception $exception) {
            return [];
        }
    }

    /**
     * 解除终端关联
     * @param integer $usid 终端编号
     * @return array
     * @throws \think\db\exception\DbException
     */
    public function delBind(int $usid): array
    {
        if ($this->isBind() && ($unid = $this->bind->getAttr('unid'))) {
            $map = ['id' => $usid, 'unid' => $unid];
            PluginAccountBind::mk()->where($map)->update(['unid' => 0]);
        }
        return $this->allBind();
    }

    /**
     * 刷新账号序号
     * @return array
     */
    public function recode(): array
    {
        if ($this->bind->isEmpty()) return $this->get();
        if ($this->user->isExists()) {
            do $check = ['code' => $this->userCode()];
            while (PluginAccountUser::mk()->master()->where($check)->findOrEmpty()->isExists());
            $this->user->save($check);
        }
        return $this->get();
    }

    /**
     * 检查是否有效
     * @return array
     * @throws \think\admin\Exception
     */
    public function check(): array
    {
        if ($this->bind->isEmpty()) {
            throw new Exception('请重新登录！', 401);
        }
        if ($this->expire > 0 && $this->auth->getAttr('time') < time()) {
            throw new Exception('登录已超时！', 403);
        }
        return static::expire()->get();
    }

    /**
     * 获取用户模型
     * @return PluginAccountUser
     */
    public function user(): PluginAccountUser
    {
        return $this->user->hidden(['sort', 'password'], true);
    }

    /**
     * 获取用户编号
     * @return string
     */
    public function getCode(): string
    {
        return $this->user->getAttr('code') ?: '';
    }

    /**
     * 获取终端类型
     * @return string
     */
    public function getType(): string
    {
        return $this->bind->getAttr('type') ?: '';
    }

    /**
     * 获取用户编号
     * @return integer
     */
    public function getUnid(): int
    {
        return intval($this->bind->getAttr('unid'));
    }

    /**
     * 获取终端编号
     * @return integer
     */
    public function getUsid(): int
    {
        return intval($this->bind->getAttr('id'));
    }

    /**
     * 生成授权令牌
     * @param boolean $expire
     * @return AccountInterface
     * @throws \think\admin\Exception
     * @throws \think\db\exception\DbException
     */
    public function token(bool $expire = true): AccountInterface
    {
        // 百分之一概率清理令牌
        if (mt_rand(1, 1000) < 10) {
            PluginAccountAuth::mk()->whereBetween('time', [1, time()])->delete();
        }
        $usid = $this->bind->getAttr('id');
        // 查询该通道历史授权记录
        if ($this->auth->isEmpty()) {
            $where = ['usid' => $usid, 'type' => $this->type];
            $this->auth = PluginAccountAuth::mk()->where($where)->findOrEmpty();
        }
        // 生成新令牌数据
        if ($this->auth->isEmpty()) {
            do $check = ['type' => $this->type, 'token' => md5(uniqid(strval(rand(0, 999))))];
            while (PluginAccountAuth::mk()->master()->where($check)->findOrEmpty()->isExists());
            $time = $this->expire > 0 ? $this->expire + time() : 0;
            $this->auth->save($check + ['usid' => $usid, 'time' => $time]);
        }
        return $expire ? $this->expire() : $this;
    }

    /**
     * 延期令牌时间
     * @return AccountInterface
     * @throws \think\admin\Exception
     */
    public function expire(): AccountInterface
    {
        if ($this->auth->isEmpty()) throw new Exception('无授权记录！');
        $this->auth->save(['time' => $this->expire > 0 ? $this->expire + time() : 0]);
        return $this;
    }

    /**
     * 更新用户资料
     * @param array $data
     * @return PluginAccountBind
     * @throws \think\admin\Exception
     */
    private function save(array $data): PluginAccountBind
    {
        if (empty($data)) throw new Exception('资料不能为空！');
        $data['extra'] = array_merge($this->bind->getAttr('extra'), $data['extra'] ?? []);
        // 写入默认头像内容
        if (empty($data['headimg']) && empty($this->bind->getAttr('headimg'))) {
            $data['headimg'] = Account::headimg();
        }
        // 自动生成账号昵称
        if (empty($data['nickname']) && empty($this->bind->getAttr('nickname'))) {
            $name = Account::get($this->type)['name'] ?? $this->type;
            $data['nickname'] = "{$name}{$this->bind->getAttr('id')}";
        }
        // 更新写入终端账号
        if ($this->bind->save($data) && $this->bind->isExists()) {
            return $this->bind->refresh();
        } else {
            throw new Exception('资料保存失败！');
        }
    }

    /**
     * 生成用户编号
     * @return string
     */
    private function userCode(): string
    {
        return CodeExtend::uniqidNumber(12, 'U');
    }
}