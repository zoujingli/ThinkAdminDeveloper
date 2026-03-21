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

namespace plugin\wechat\client\controller;

use plugin\storage\service\LocalStorage;
use plugin\wechat\client\service\WechatService;
use think\admin\Controller;
use think\admin\helper\FormBuilder;

/**
 * 微信授权绑定.
 * @class Config
 */
class Config extends Controller
{
    /**
     * 微信授权配置.
     * @auth true
     * @menu true
     * @throws \think\admin\Exception
     */
    public function options()
    {
        $this->thrNotify = apiuri('wechat-client/push/index', [], false, true);
        if ($this->request->isGet()) {
            try {
                // 生成微信授权链接
                $source = enbase64url(sysuri('system/index/index', [], false, true) . '#' . $this->request->url());
                $authurl = sysconf('wechat.service_authurl|raw') ?: 'https://open.cuci.cc/api/plugin-wechat-service/push/auth?source=SOURCE';
                $this->authurl = str_replace('source=SOURCE', "source={$source}", $authurl);
                // 授权成功后的参数保存
                if (input('?appid') && input('?appkey')) {
                    sysconf('wechat.type', 'thr');
                    sysconf('wechat.thr_appid', input('appid'));
                    sysconf('wechat.thr_appkey', input('appkey'));
                    WechatService::ThinkServiceConfig()->setApiNotifyUri($this->thrNotify);
                }
                // 读取授权的微信参数
                $this->wechat = WechatService::ThinkServiceConfig()->getConfig();
            } catch (\Exception $exception) {
                $this->wechat = [];
                $this->message = $exception->getMessage();
            }
            $this->geoip = $this->app->cache->get('mygeoip', '');
            if (empty($this->geoip)) {
                $this->geoip = gethostbyname($this->request->host());
                $this->app->cache->set('mygeoip', $this->geoip, 360);
            }
            $this->title = '微信授权配置';
            $this->fetch();
        } else {
            foreach ($this->request->post() as $k => $v) {
                sysconf($k, $v);
            }
            if ($this->request->post('wechat.type') === 'thr') {
                try {
                    WechatService::ThinkServiceConfig()->setApiNotifyUri($this->thrNotify);
                } catch (\Exception $exception) {
                    $this->error($exception->getMessage());
                }
            }
            sysoplog('微信授权配置', '修改微信授权配置成功');
            $this->success('微信授权修改成功！', system_uri('', ['uniqid' => uniqid()]));
        }
    }

    /**
     * 微信授权测试.
     * @auth true
     */
    public function options_test()
    {
        $this->fetch();
    }

    /**
     * 微信第三方平台接口配置.
     * @auth true
     * @throws \think\admin\Exception
     */
    public function options_jsonrpc()
    {
        $builder = $this->buildJsonrpcForm();
        if ($this->request->isGet()) {
            $authUrl = sysconf('wechat.service_authurl|raw') ?: 'https://open.cuci.cc/api/plugin-wechat-service/push/auth?source=SOURCE';
            $jsonRpc = sysconf('wechat.service_jsonrpc|raw') ?: 'https://open.cuci.cc/api/plugin-wechat-service/client/jsonrpc?token=TOKEN';
            $builder->fetch(['vo' => ['auth_url' => $authUrl, 'json_rpc' => $jsonRpc]]);
        } else {
            $data = $builder->validate();
            sysconf('wechat.service_authurl', $data['auth_url']);
            sysconf('wechat.service_jsonrpc', $data['json_rpc']);
            $this->success('接口地址保存成功！');
        }
    }

    /**
     * 绑定小程序.
     * @auth true
     * @throws \think\admin\Exception
     */
    public function options_wxapp()
    {
        $builder = $this->buildWxappForm();
        if ($this->request->isGet()) {
            $data = sysdata('plugin.wechat.wxapp') ?: [];
            $builder->fetch(['vo' => ['appid' => $data['appid'] ?? '', 'appkey' => $data['appkey'] ?? '']]);
        } else {
            sysdata('plugin.wechat.wxapp', $builder->validate());
            $this->success('参数保存成功！');
        }
    }

    /**
     * 微信支付配置.
     * @auth true
     * @menu true
     * @throws \think\admin\Exception
     */
    public function payment()
    {
        if ($this->request->isGet()) {
            $this->title = '微信支付配置';
            $local = LocalStorage::instance();
            $this->data = sysdata('plugin.wechat.payment');
            $this->mch_ssl_cer = $this->data['mch_ssl_cer'] ?? '';
            $this->mch_ssl_key = $this->data['mch_ssl_key'] ?? '';
            $this->mch_ssl_p12 = $this->data['mch_ssl_p12'] ?? '';
            $this->mch_ssl_pay = $this->data['mch_ssl_pay'] ?? '';
            if (!$local->has($this->mch_ssl_cer, true)) {
                $this->mch_ssl_cer = '';
            }
            if (!$local->has($this->mch_ssl_key, true)) {
                $this->mch_ssl_key = '';
            }
            if (!$local->has($this->mch_ssl_p12, true)) {
                $this->mch_ssl_p12 = '';
            }
            if (!$local->has($this->mch_ssl_pay, true)) {
                $this->mch_ssl_pay = '';
            }
            $this->fetch();
        } else {
            $this->error('抱歉，数据提交地址错误！');
        }
    }

    /**
     * 微信支付修改.
     * @auth true
     * @throws \think\admin\Exception
     */
    public function payment_save()
    {
        if ($this->request->isPost()) {
            $local = LocalStorage::instance();
            $wechat = $this->request->post('wechat');
            // if (empty($wechat['mch_pay_sid'])) {
            //     $this->error('微信支付公钥序号为空！');
            // }
            // if (empty($wechat['mch_ssl_pay']) || !$local->has($wechat['mch_ssl_pay'], true)) {
            //     $this->error('微信支付公钥不能为空！');
            // }
            // PEM 证书模式处理
            if ($wechat['mch_ssl_type'] === 'pem') {
                if (empty($wechat['mch_ssl_key']) || !$local->has($wechat['mch_ssl_key'], true)) {
                    $this->error('商户证书密钥不能为空！');
                }
                if (empty($wechat['mch_ssl_cer']) || !$local->has($wechat['mch_ssl_cer'], true)) {
                    $this->error('商户证书公钥不能为空！');
                }
            }
            // P12 证书模式转 PEM 模式
            if ($wechat['mch_ssl_type'] === 'p12') {
                if (empty($wechat['mch_ssl_p12']) || !$local->has($wechat['mch_ssl_p12'], true)) {
                    $this->error('商户证书 P12 不能为空！');
                }
                if (openssl_pkcs12_read($local->get($wechat['mch_ssl_p12'], true), $certs, $wechat['mch_id'])) {
                    $name1 = sprintf('wxpay/%s_%s_cer.pem', $wechat['mch_id'], md5($certs['cert']));
                    $name2 = sprintf('wxpay/%s_%s_key.pem', $wechat['mch_id'], md5($certs['pkey']));
                    $wechat['mch_ssl_cer'] = $local->set($name1, $certs['cert'], true)['url'];
                    $wechat['mch_ssl_key'] = $local->set($name2, $certs['pkey'], true)['url'];
                    $wechat['mch_ssl_type'] = 'pem';
                } else {
                    $this->error('商户账号与 P12 证书不匹配！');
                }
            }
            // 记录文本格式参数，兼容分布式部署
            sysdata('plugin.wechat.payment', [
                'appid' => WechatService::getAppid(),
                'mch_id' => $wechat['mch_id'],
                'mch_key' => $wechat['mch_key'],
                'mch_v3_key' => $wechat['mch_v3_key'],
                'mch_ssl_cer' => $wechat['mch_ssl_cer'],
                'mch_ssl_key' => $wechat['mch_ssl_key'],
                'mch_ssl_pay' => $wechat['mch_ssl_pay'],
                'mch_pay_sid' => $wechat['mch_pay_sid'] ?? '',
                'ssl_pay_text' => $local->get($wechat['mch_ssl_pay'], true),
                'ssl_cer_text' => $local->get($wechat['mch_ssl_cer'], true),
                'ssl_key_text' => $local->get($wechat['mch_ssl_key'], true),
            ]);
            // 同步更新证书内容
            WechatService::withWxpayCert(['mch_id' => $wechat['mch_id']]);
            // 记录操作历史并返回保存结果
            sysoplog('微信支付配置', '修改微信支付配置成功');
            $this->success('微信支付配置成功！');
        } else {
            $this->error('抱歉，访问方式错误！');
        }
    }

    /**
     * 微信支付测试.
     * @auth true
     */
    public function payment_test()
    {
        $this->fetch();
    }

    /**
     * 构建第三方平台接口表单.
     */
    private function buildJsonrpcForm(): FormBuilder
    {
        return FormBuilder::mk()
            ->addTextInput('auth_url', '公众号授权跳转入口', 'Getway', true, '进行微信授权时会跳转到这个页面，由微信管理员扫二维码进行授权。', '^https?://.*?auth.*?source=SOURCE', [
                'required-error' => '授权跳转不能为空！',
                'pattern-error' => '授权跳转入口格式错误！',
            ])
            ->addTextInput('json_rpc', '第三方服务平台接口', 'JsonRpc', true, '由应用插件 <a target="_blank" href="https://thinkadmin.top/plugin/think-plugs-wechat-service.html">ThinkPlugsWechatService</a> 提供的第三方服务平台 JSON-RPC 接口地址。', '^https?://.*?jsonrpc.*?token=TOKEN', [
                'required-error' => '接口地址不能为空！',
                'pattern-error' => '接口地址格式错误！',
            ])
            ->addSubmitButton('保存参数')
            ->addCancelButton();
    }

    /**
     * 构建小程序绑定表单.
     */
    private function buildWxappForm(): FormBuilder
    {
        return FormBuilder::mk()
            ->addTextInput('appid', '小程序', 'AppId', true, '<b>必选</b>，微信小程序 AppID 需要微信公众号平台获取！', '^wx[0-9a-z]{16}$', [
                'maxlength' => 18,
                'required-error' => '小程序ID不能为空！',
                'pattern-error' => '小程序ID格式错误！',
            ])
            ->addTextInput('appkey', '小程序密钥', 'AppSecret', true, '<b>必选</b>，微信小程序 AppSecret 需要微信公众号平台获取！', '^[0-9a-z]{32}$', [
                'maxlength' => 32,
                'required-error' => '小程序密钥不能为空！',
                'pattern-error' => '小程序密钥格式错误！',
            ])
            ->addSubmitButton('保存参数')
            ->addCancelButton();
    }
}
