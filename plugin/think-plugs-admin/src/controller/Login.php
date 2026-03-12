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

namespace plugin\admin\controller;

use think\admin\Controller;
use think\admin\Exception;
use think\admin\extend\codec\CodeToolkit;
use think\admin\model\SystemUser;
use think\admin\auth\AdminService;
use think\admin\auth\CaptchaService;
use think\admin\runtime\RuntimeService;
use think\admin\system\SystemService;
use think\exception\HttpResponseException;

/**
 * 用户登录管理.
 * @class Login
 */
class Login extends Controller
{
    /**
     * 后台登录入口.
     * @throws Exception
     */
    public function index()
    {
        if ($this->app->request->isGet()) {
            if (AdminService::isLogin()) {
                $this->redirect(sysuri('admin/index/index'));
            } else {
                // 加载登录模板
                $this->title = '系统登录';
                // 登录验证令牌
                $this->captchaType = 'LoginCaptcha';
                $this->captchaToken = CodeToolkit::uuid();
                // 当前运行模式
                $this->runtimeMode = RuntimeService::check();
                $this->tokenValueJson = json_encode('', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $this->tokenBootstrapJson = json_encode(AdminService::getBootstrapQuery(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                // 后台背景处理
                $images = str2arr(sysconf('login_image|raw') ?: '', '|');
                if (empty($images)) {
                    $images = [
                        SystemService::uri('/static/theme/img/login/bg1.jpg'),
                        SystemService::uri('/static/theme/img/login/bg2.jpg'),
                    ];
                }
                $this->loginStyle = sprintf('style="background-image:url(%s)" data-bg-transition="%s"', $images[0], join(',', $images));
                // 更新后台主域名，用于部分无法获取域名的场景调用
                if ($this->request->domain() !== sysconf('base.site_host|raw')) {
                    sysconf('base.site_host', $this->request->domain());
                }
                $this->fetch();
            }
        } else {
            $data = $this->_vali([
                'username.require' => '登录账号不能为空!',
                'username.min:4' => '账号不能少于4位字符!',
                'password.require' => '登录密码不能为空!',
                'password.min:4' => '密码不能少于4位字符!',
                'verify.require' => '图形验证码不能为空!',
                'uniqid.require' => '图形验证标识不能为空!',
            ]);
            if (!CaptchaService::instance()->check($data['verify'], $data['uniqid'])) {
                $this->error('图形验证码验证失败，请重新输入!');
            }
            /* ! 用户信息验证 */
            $user = SystemUser::mk()->where(['username' => $data['username']])->findOrEmpty();
            if ($user->isEmpty()) {
                $this->markCaptchaError($data['uniqid']);
                $this->error('登录账号或密码错误，请重新输入!');
            }
            if (empty($user['status'])) {
                $this->markCaptchaError($data['uniqid']);
                $this->error('账号已经被禁用，请联系管理员!');
            }
            if (md5("{$user['password']}{$data['uniqid']}") !== $data['password']) {
                $this->markCaptchaError($data['uniqid']);
                $this->error('登录账号或密码错误，请重新输入!');
            }
            // 登录态签发 JWT 需要保留密码摘要参与载荷校验。
            AdminService::login($user->toArray());
            $this->clearCaptchaError($data['uniqid']);
            $token = AdminService::buildToken();
            // 更新登录次数
            $user->where(['id' => $user->getAttr('id')])->inc('login_num')->update([
                'login_at' => date('Y-m-d H:i:s'), 'login_ip' => $this->app->request->ip(),
            ]);
            sysoplog('系统用户登录', '登录系统后台成功');
            $this->success('登录成功', sysuri('admin/index/index', [
                AdminService::getBootstrapQuery() => AdminService::buildBootstrap($token),
            ]));
        }
    }

    /**
     * 生成验证码
     */
    public function captcha()
    {
        $input = $this->_vali([
            'type.require' => '类型不能为空!',
            'token.require' => '标识不能为空!',
        ]);
        $image = CaptchaService::instance()->initialize();
        $captcha = ['image' => $image->getData(), 'uniqid' => $image->getUniqid()];
        $this->rememberCaptchaToken($captcha['uniqid'], $input['type'], $input['token']);
        // 未发生异常时，直接返回验证码内容
        if (!$this->hasCaptchaError($input['type'], $input['token'])) {
            $captcha['code'] = $image->getCode();
        }
        $this->success('生成验证码成功', $captcha);
    }

    /**
     * 退出登录.
     */
    public function out()
    {
        AdminService::logout();
        throw new HttpResponseException(json([
            'code' => 1,
            'info' => lang('退出登录成功!'),
            'data' => sysuri('admin/login/index'),
            'token' => '',
        ]));
    }

    /**
     * 记录验证码与登录页标识关联.
     */
    private function rememberCaptchaToken(string $uniqid, string $type, string $token): void
    {
        $this->app->cache->set($this->captchaMapKey($uniqid), ['type' => $type, 'token' => $token], 600);
    }

    /**
     * 标记登录验证码失败.
     */
    private function markCaptchaError(string $uniqid): void
    {
        $map = $this->app->cache->get($this->captchaMapKey($uniqid), []);
        if (!empty($map['type']) && !empty($map['token'])) {
            $this->app->cache->set($this->captchaFailKey($map['type'], $map['token']), 1, 600);
        }
    }

    /**
     * 清理登录验证码失败标记.
     */
    private function clearCaptchaError(string $uniqid): void
    {
        $map = $this->app->cache->get($this->captchaMapKey($uniqid), []);
        if (!empty($map['type']) && !empty($map['token'])) {
            $this->app->cache->delete($this->captchaFailKey($map['type'], $map['token']));
        }
        $this->app->cache->delete($this->captchaMapKey($uniqid));
    }

    /**
     * 判断当前登录页是否已有验证码失败记录.
     */
    private function hasCaptchaError(string $type, string $token): bool
    {
        return boolval($this->app->cache->get($this->captchaFailKey($type, $token), 0));
    }

    /**
     * 登录页验证码失败缓存键.
     */
    private function captchaFailKey(string $type, string $token): string
    {
        return 'think.admin.login.captcha.fail.' . md5("{$type}:{$token}");
    }

    /**
     * 验证码映射缓存键.
     */
    private function captchaMapKey(string $uniqid): string
    {
        return 'think.admin.login.captcha.map.' . md5($uniqid);
    }
}
