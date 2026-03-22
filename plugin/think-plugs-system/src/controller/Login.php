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

namespace plugin\system\controller;

use plugin\system\model\SystemUser;
use plugin\system\service\AuthService;
use plugin\system\service\SystemService;
use plugin\system\service\UserService;
use think\admin\Controller;
use think\admin\extend\CodeToolkit;
use think\admin\service\ImageSliderVerify;
use think\admin\service\RuntimeService;
use think\exception\HttpResponseException;

/**
 * 用户登录管理.
 * @class Login
 */
class Login extends Controller
{
    private const LOGIN_VERIFY_TTL = 1800;

    /**
     * 后台登录入口.
     */
    public function index()
    {
        if ($this->app->request->isGet()) {
            if (AuthService::isLogin()) {
                $this->redirect(sysuri('system/index/index'));
            } else {
                // 加载登录模板
                $this->title = '系统登录';
                // 登录页标识与密码加密公钥
                $this->loginToken = CodeToolkit::uuid();
                $this->loginPasswordKey = $this->rememberPasswordCipher($this->loginToken);
                // 当前运行模式
                $this->runtimeMode = RuntimeService::check();
                $this->tokenValueJson = json_encode('', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                // 后台背景处理
                $images = array_values(array_filter(array_map('strval', (array)sysget('system.site.login_background_images', []))));
                if (empty($images)) {
                    $images = [
                        SystemService::uri('/static/theme/img/login/bg1.jpg'),
                        SystemService::uri('/static/theme/img/login/bg2.jpg'),
                    ];
                }
                $this->loginStyle = sprintf('style="background-image:url(%s)" data-bg-transition="%s"', $images[0], join(',', $images));
                // 更新后台主域名，用于部分无法获取域名的场景调用
                if ($this->request->domain() !== strval(sysdata('system.site.host') ?: '')) {
                    sysdata('system.site.host', $this->request->domain());
                }
                $this->renderLoginPage();
            }
        } else {
            $data = $this->_vali([
                'username.require' => '登录账号不能为空!',
                'username.min:4' => '账号不能少于4位字符!',
                'password.require' => '登录密码不能为空!',
                'token.require' => '登录页面标识不能为空!',
                'password_mode.default' => 'plain',
                'verify.default' => '',
                'uniqid.default' => '',
            ]);
            $token = strval($data['token']);
            $password = $this->resolveLoginPassword(strval($data['password']), $token, strval($data['password_mode']));
            if (strlen($password) < 4) {
                $this->error('密码不能少于4位字符!');
            }
            if ($this->hasVerifyError($token)) {
                if ($data['uniqid'] === '' || $data['verify'] === '') {
                    $this->error('请先完成滑块验证!', ['need_verify' => true, 'refresh_verify' => true]);
                }
                if (ImageSliderVerify::verify(strval($data['uniqid']), strval($data['verify']), true) !== 1) {
                    $this->error('滑块验证失败，请重新拖动!', ['need_verify' => true, 'refresh_verify' => true]);
                }
            }
            /* ! 用户信息验证 */
            $user = SystemUser::mk()->where(['username' => $data['username']])->findOrEmpty();
            if ($user->isEmpty()) {
                $this->markVerifyError($token);
                $this->error('登录账号或密码错误，请重新输入!', ['need_verify' => true, 'refresh_verify' => true]);
            }
            if (empty($user['status'])) {
                $this->markVerifyError($token);
                $this->error('账号已经被禁用，请联系管理员!', ['need_verify' => true, 'refresh_verify' => true]);
            }
            if (!UserService::verifyPassword($password, strval($user['password']))) {
                $this->markVerifyError($token);
                $this->error('登录账号或密码错误，请重新输入!', ['need_verify' => true, 'refresh_verify' => true]);
            }
            // 登录态签发 JWT 需要保留密码摘要参与载荷校验。
            AuthService::login($user->toArray());
            $this->clearVerifyError($token);
            $this->clearPasswordCipher($token);
            $token = AuthService::buildToken();
            AuthService::syncTokenCookie($token);

            if (function_exists('worker_auth_debug_enabled') && worker_auth_debug_enabled()) {
                worker_auth_debug('system.login.success', [
                    'username' => strval($user['username']),
                    'user_id' => intval($user['id']),
                    'session_id' => AuthService::currentSessionId(),
                    'token' => worker_auth_token_snapshot($token),
                    'request_cookie' => worker_auth_token_snapshot(strval($this->request->cookie(AuthService::getTokenCookie(), ''))),
                ]);
            }

            // 更新登录次数
            $user->where(['id' => $user->getAttr('id')])->inc('login_num')->update([
                'login_at' => date('Y-m-d H:i:s'), 'login_ip' => $this->app->request->ip(),
            ]);
            sysoplog('系统用户登录', '登录系统后台成功');
            $this->success('登录成功', sysuri('system/index/index'));
        }
    }

    /**
     * 生成滑块验证数据。
     */
    public function slider()
    {
        $input = $this->_vali([
            'token.require' => '登录页面标识不能为空!',
        ]);
        $images = $this->sliderImages();
        $slider = ImageSliderVerify::render($images[array_rand($images)], self::LOGIN_VERIFY_TTL);
        $this->success('生成拼图成功', [
            'bgimg' => $slider['bgimg'],
            'water' => $slider['water'],
            'uniqid' => $slider['code'],
            'width' => $slider['width'],
            'height' => $slider['height'],
            'piece_width' => $slider['piece_width'],
            'token' => $input['token'],
        ]);
    }

    /**
     * 向后兼容旧验证码接口。
     */
    public function captcha()
    {
        $this->slider();
    }

    /**
     * 检查滑块结果。
     */
    public function check()
    {
        $data = $this->_vali([
            'uniqid.require' => '拼图验证标识不能为空!',
            'verify.require' => '拼图位置不能为空!',
        ]);
        $state = ImageSliderVerify::verify(strval($data['uniqid']), strval($data['verify']));
        $this->success('验证结果', ['state' => $state]);
    }

    /**
     * 退出登录.
     */
    public function out()
    {
        AuthService::logout();
        AuthService::forgetTokenCookie();
        throw new HttpResponseException(json([
            'code' => 1,
            'info' => lang('退出登录成功!'),
            'data' => sysuri('system/login/index'),
            'token' => '',
        ]));
    }

    /**
     * 记录登录页密码加密私钥。
     */
    private function rememberPasswordCipher(string $token): string
    {
        if (!function_exists('openssl_pkey_new')) {
            return '';
        }
        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        if ($resource === false) {
            return '';
        }
        if (!openssl_pkey_export($resource, $privateKey)) {
            return '';
        }
        $detail = openssl_pkey_get_details($resource);
        $publicKey = preg_replace('/-----BEGIN PUBLIC KEY-----|-----END PUBLIC KEY-----|\s+/', '', strval($detail['key'] ?? ''));
        if ($privateKey !== '' && $publicKey !== '') {
            $this->app->cache->set($this->passwordCipherKey($token), $privateKey, self::LOGIN_VERIFY_TTL);
            return $publicKey;
        }
        return '';
    }

    /**
     * 解密登录密码。
     */
    private function resolveLoginPassword(string $password, string $token, string $mode): string
    {
        if (strtolower($mode) !== 'rsa') {
            return $password;
        }
        $privateKey = trim(strval($this->app->cache->get($this->passwordCipherKey($token), '')));
        if ($privateKey === '') {
            $this->error('登录页面已过期，请刷新后重试!', ['reload' => true]);
        }
        $binary = base64_decode($password, true);
        if ($binary === false || !openssl_private_decrypt($binary, $plain, $privateKey, OPENSSL_PKCS1_OAEP_PADDING)) {
            $this->error('登录密码解密失败，请刷新页面后重试!', ['reload' => true]);
        }
        return strval($plain);
    }

    /**
     * 清理登录页密码私钥。
     */
    private function clearPasswordCipher(string $token): void
    {
        $this->app->cache->delete($this->passwordCipherKey($token));
    }

    /**
     * 标记登录验证失败。
     */
    private function markVerifyError(string $token): void
    {
        $this->app->cache->set($this->verifyErrorKey($token), 1, self::LOGIN_VERIFY_TTL);
    }

    /**
     * 清理登录验证失败标记。
     */
    private function clearVerifyError(string $token): void
    {
        $this->app->cache->delete($this->verifyErrorKey($token));
    }

    /**
     * 判断当前登录页是否需要滑块验证。
     */
    private function hasVerifyError(string $token): bool
    {
        return boolval($this->app->cache->get($this->verifyErrorKey($token), 0));
    }

    /**
     * 获取登录滑块候选图片。
     */
    private function sliderImages(): array
    {
        return [
            syspath('public/static/theme/img/login/bg1.jpg'),
            syspath('public/static/theme/img/login/bg2.jpg'),
        ];
    }

    /**
     * 登录失败标记缓存键。
     */
    private function verifyErrorKey(string $token): string
    {
        return 'think.admin.login.verify.fail.' . hash('sha256', $token);
    }

    /**
     * 登录密码私钥缓存键。
     */
    private function passwordCipherKey(string $token): string
    {
        return 'think.admin.login.password.cipher.' . hash('sha256', $token);
    }

    /**
     * 返回禁用缓存的登录页面。
     */
    private function renderLoginPage(): void
    {
        $vars = get_object_vars($this);
        throw new HttpResponseException(view('', $vars)->header($this->noStoreHeaders()));
    }

    /**
     * 登录页缓存控制头。
     *
     * @return array<string, string>
     */
    private function noStoreHeaders(): array
    {
        return [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];
    }
}
