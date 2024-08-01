<?php

// +----------------------------------------------------------------------
// | Account Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
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

namespace plugin\account\controller\api;

use plugin\account\model\PluginAccountUser;
use plugin\account\service\Account;
use plugin\account\service\Message;
use think\admin\Controller;
use think\admin\extend\CodeExtend;
use think\admin\extend\ImageVerify;
use think\admin\extend\JwtExtend;
use think\exception\HttpResponseException;

/**
 * 通用登录注册接口
 * @class Login
 * @package plugin\account\controller\api
 */
class Login extends Controller
{
    /**
     * 通过手机号登录
     * @return void
     */
    public function in()
    {
        try {
            $data = $this->_vali([
                'type.require'   => '类型为空',
                'phone.mobile'   => '手机号错误',
                'phone.require'  => '手机号为空',
                'verify.require' => '验证码为空'
            ]);
            if (Account::field($data['type']) !== 'phone') {
                $this->error('不支持登录');
            }
            if (Message::checkVerifyCode($data['verify'], $data['phone'])) {
                Message::clearVerifyCode($data['phone']);
                $inset = ['phone' => $data['phone'], 'deleted' => 0];
                if (Account::enableAutoReigster()) {
                    $account = Account::mk($data['type']);
                    $account->set($inset);
                } else {
                    // 通过手机查询所有终端
                    $account = Account::mk('', $inset);
                    if ($account->isNull()) $this->error('手机未注册');
                    // 如果当前终端账号不存在则创建
                    if ($account->getType() !== $data['type']) {
                        $account = Account::mk($data['type'], $inset);
                        $account->isNull() && $account->set($inset);
                    }
                }
                $account->isBind() || $account->bind($inset, $inset);
                $this->success('登录成功', $account->expire()->get(true));
            } else {
                $this->error('短信验证失败');
            }
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * 自动授权登录
     * @return void
     */
    public function auto()
    {
        try {
            $data = $this->_vali(['code.require' => '授权编号为空！']);
            $vars = CodeExtend::decrypt($data['code'], JwtExtend::jwtkey());
            if (is_array($vars) && isset($vars['unid'])) {
                $user = PluginAccountUser::mk()->findOrEmpty($vars['unid']);
                if ($user->isEmpty()) $this->error('无效账号！');
                $inset = ['phone' => $user->getAttr('phone')];
                $account = Account::mk(Account::WAP, $inset);
                $account->set(['unid' => $user->getAttr('id')] + $inset);
                $this->success('登录成功！', $account->token()->get(true));
            } else {
                $this->error('解密失败！');
            }
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * 通过密码登录
     * @return void
     */
    public function pass()
    {
        try {
            $data = $this->_vali([
                'type.require'     => '接口类型为空',
                'phone.mobile'     => '登录手机错误',
                'phone.require'    => '登录手机为空',
                'uniqid.require'   => '拼图编号为空',
                'verify.require'   => '拼图位置为空',
                'password.require' => '登录密码为空',
            ]);
            if (Account::field($data['type']) !== 'phone') {
                $this->error('不支持密码');
            }
            if (ImageVerify::verify($data['uniqid'], $data['verify'], true) !== 1) {
                $this->error('拼图验证失败');
            }
            $inset = ['phone' => $data['phone'], 'deleted' => 0];
            // 通过手机查询所有终端
            $account = Account::mk('', $inset);
            if ($account->isNull()) $this->error('手机未注册');
            if ($account->pwdVerify($data['password'])) {
                // 如果当前终端账号不存在则创建
                if ($account->getType() !== $data['type']) {
                    $account = Account::mk($data['type'], $inset);
                    $account->isNull() && $account->set($inset);
                }
                $account->isBind() || $account->bind($inset, $inset);
                $this->success('登录成功', $account->expire()->get(true));
            } else {
                $this->error('密码错误');
            }
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * 通过短信找回密码
     * @return void
     */
    public function forget()
    {
        try {
            $data = $this->_vali([
                'type.require'   => '接口类型为空',
                'phone.mobile'   => '登录手机错误',
                'phone.require'  => '登录手机为空',
                'verify.require' => '短信验证为空',
                'passwd.require' => '密码不能为空',
            ]);
            if (Message::checkVerifyCode($data['verify'], $data['phone'], Message::tForget)) {
                Message::clearVerifyCode($data['phone'], Message::tForget);
                $inset = ['phone' => $data['phone'], 'deleted' => 0];
                $account = Account::mk($data['type'], $inset);
                if ($account->isNull()) $this->error('账号不存在');
                $account->pwdModify($data['passwd']);
                $this->success('重置成功', $account->expire()->get(true));
            } else {
                $this->error('验证码错误');
            }
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * 用户注册绑定
     * @return void
     */
    public function register()
    {
        try {
            $data = $this->_vali([
                'type.require'   => '接口类型为空',
                'phone.mobile'   => '登录手机错误',
                'phone.require'  => '登录手机为空',
                'verify.require' => '短信验证为空',
                'passwd.require' => '密码不能为空',
                'fphone.default' => ''
            ]);
            if (Message::checkVerifyCode($data['verify'], $data['phone'], Message::tRegister)) {
                Message::clearVerifyCode($data['phone'], Message::tRegister);
                $account = Account::mk($data['type']);
                $account->set($inset = ['phone' => $data['phone'], 'deleted' => 0]);
                $account->isBind() || $account->bind($inset, $inset);
                $account->pwdModify($data['passwd']);
                // 触发注册事件
                $this->app->event->trigger('PluginAccountRegister', $account);
                $this->success('注册成功', $account->get(true));
            } else {
                $this->error('短信验证失败');
            }
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * 发送短信验证码
     * @return void
     */
    public function send()
    {
        $data = $this->_vali([
            'type.default'   => 'login',
            'phone.mobile'   => '手机号错误',
            'phone.require'  => '手机号为空',
            'uniqid.require' => '拼图编号为空',
            'verify.require' => '拼图位置为空',
        ]);
        // 发送手机短信验证码
        if (ImageVerify::verify($data['uniqid'], $data['verify'], true) === 1) {
            if (isset(Message::$scenes[$type = strtoupper($data['type'])])) {
                [$state, $info, $result] = Message::sendVerifyCode($data['phone'], 120, $type);
                $state ? $this->success($info, $result) : $this->error($info);
            } else {
                $this->error('无效通道');
            }
        } else {
            $this->error('验证码错误');
        }
    }

    /**
     * 生成拼图验证码
     * @return void
     */
    public function image()
    {
        $images = [
            syspath('public/static/theme/img/login/bg1.jpg'),
            syspath('public/static/theme/img/login/bg2.jpg'),
        ];
        $image = ImageVerify::render($images[array_rand($images)]);
        $this->success('生成拼图成功', [
            'bgimg'  => $image['bgimg'],
            'water'  => $image['water'],
            'uniqid' => $image['code'],
        ]);
    }

    /**
     * 实时验证结果
     * @return void
     */
    public function verify()
    {
        $data = $this->_vali([
            'uniqid.require' => '拼图验证为空',
            'verify.require' => '拼图数值为空'
        ]);
        // state: [ -1:需要刷新, 0:验证失败, 1:验证成功 ]
        $state = ImageVerify::verify($data['uniqid'], $data['verify']);
        $this->success('验证结果', ['state' => $state]);
    }
}