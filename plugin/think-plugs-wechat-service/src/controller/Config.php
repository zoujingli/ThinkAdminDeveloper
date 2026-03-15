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

namespace plugin\wechat\service\controller;

use think\admin\Controller;
use think\admin\Exception;
use think\admin\helper\FormBuilder;

class Config extends Controller
{
    /**
     * @auth true
     * @menu true
     */
    public function index()
    {
        $this->title = '开放平台配置';
        $this->geoip = $this->app->cache->get('mygeoip', '');
        if (empty($this->geoip)) {
            $this->geoip = gethostbyname($this->request->host());
            $this->app->cache->set('mygeoip', $this->geoip, 360);
        }
        $this->fetch();
    }

    /**
     * @auth true
     * @throws Exception
     */
    public function edit()
    {
        $this->_applyFormToken();
        $builder = $this->buildConfigForm();

        if ($this->request->isGet()) {
            $builder->fetch(['vo' => $this->loadConfigFormData()]);
            return;
        }

        $data = $builder->validate();
        sysconf('service.component_appid', $data['component_appid']);
        sysconf('service.component_appsecret', $data['component_appsecret']);
        sysconf('service.component_token', $data['component_token']);
        sysconf('service.component_encodingaeskey', $data['component_encodingaeskey']);
        $this->success('参数修改成功！');
    }

    private function buildConfigForm(): FormBuilder
    {
        return FormBuilder::mk()
            ->addTextInput('component_appid', '开放平台账号', 'AppID', true, '开放平台账号 AppID，需要在微信开放平台获取。', '^.{18}$', [
                'maxlength' => 18,
                'required-error' => '开放平台账号不能为空！',
                'pattern-error' => '开放平台账号格式错误！',
            ])
            ->addTextInput('component_appsecret', '开放平台密钥', 'AppSecret', true, '开放平台密钥 AppSecret，需要在微信开放平台获取。', '^.{32}$', [
                'maxlength' => 32,
                'required-error' => '开放平台密钥不能为空！',
                'pattern-error' => '开放平台密钥格式错误！',
            ])
            ->addTextInput('component_token', '开放平台消息校验', 'Token', true, '开发者在代替微信接收到消息时，用此 TOKEN 来校验消息。', null, [
                'required-error' => '开放平台消息校验不能为空！',
            ])
            ->addTextInput('component_encodingaeskey', '开放平台消息加解密', 'AesKey', true, '在代替微信收发消息时使用，必须是长度为 43 位字母和数字组合的字符串。', '^.{43}$', [
                'maxlength' => 43,
                'required-error' => '开放平台消息加解密不能为空！',
                'pattern-error' => '开放平台消息加解密格式错误！',
            ])
            ->addSubmitButton()
            ->addCancelButton();
    }

    private function loadConfigFormData(): array
    {
        return [
            'component_appid' => strval(sysconf('service.component_appid')),
            'component_appsecret' => strval(sysconf('service.component_appsecret')),
            'component_token' => strval(sysconf('service.component_token')),
            'component_encodingaeskey' => strval(sysconf('service.component_encodingaeskey')),
        ];
    }
}
