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

namespace plugin\wechat\service\controller\api;

use plugin\wechat\service\model\WechatAuth;
use plugin\wechat\service\service\AuthService;
use think\admin\Controller;
use think\admin\service\JsonRpcHttpServer;
use think\Exception;
use think\exception\HttpResponseException;

/**
 * 接口获取实例化
 * Class Client.
 */
class Client extends Controller
{
    /**
     * YAR 标准接口.
     */
    public function yar()
    {
        try {
            $service = new \Yar_Server($this->instance());
            $service->handle();
        } catch (\Exception $exception) {
            throw new HttpResponseException(response($exception->getMessage()));
        }
    }

    /**
     * SOAP 标准接口.
     */
    public function soap()
    {
        try {
            $server = new \SoapServer(null, ['uri' => 'thinkadmin']);
            $server->setObject($this->instance());
            $server->handle();
        } catch (\Exception $exception) {
            throw new HttpResponseException(response($exception->getMessage()));
        }
    }

    /**
     * JsonRpc 标准接口.
     */
    public function jsonrpc()
    {
        try {
            JsonRpcHttpServer::instance()->handle($this->instance());
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            throw new HttpResponseException(response($exception->getMessage()));
        }
    }

    /**
     * 远程获取实例对象
     * @return mixed
     */
    private function instance()
    {
        try {
            $data = json_decode(debase64url(input('token', '')), true);
            if (empty($data) || !is_array($data)) {
                throw new Exception(lang('请求 TOKEN 格式错误！'));
            }
            [$class, $appid, $time, $nostr, $sign] = [$data['class'], $data['appid'], $data['time'], $data['nostr'], $data['sign']];
            if (empty($class) || empty($appid) || empty($time) || empty($nostr) || empty($sign)) {
                throw new Exception(lang('请求 TOKEN 格式异常！'));
            }
            // 接口请求参数检查验证
            $auth = WechatAuth::mk()->where(['authorizer_appid' => $appid])->findOrEmpty();
            if ($auth->isEmpty()) {
                throw new Exception(lang('该公众号还未授权，请重新授权！'));
            }
            if (empty($auth['status'])) {
                throw new Exception(lang('该公众号已被禁用，请联系管理员！'));
            }
            if (abs(time() - $data['time']) > 3600) {
                throw new Exception(lang('请求时间与服务器时差过大，请同步时间！'));
            }
            if (md5("{$class}#{$appid}#{$auth['appkey']}#{$time}#{$nostr}") !== $sign) {
                throw new Exception(lang('该公众号%s请求签名异常！', [$appid]));
            }
            $auth->where(['id' => $auth->getAttr('id')])->inc('total')->update([]);
            return AuthService::__callStatic($class, [$appid]);
        } catch (\Exception $exception) {
            return new \Exception($exception->getMessage(), 404);
        }
    }
}
