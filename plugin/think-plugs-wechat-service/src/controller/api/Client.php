<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | Payment Plugin for ThinkAdmin
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

namespace plugin\wechat\service\controller\api;

use plugin\wechat\service\AuthService;
use plugin\wechat\service\model\WechatAuth;
use think\admin\Controller;
use think\admin\extend\JsonRpcServer;
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
            JsonRpcServer::instance()->handle($this->instance());
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
                throw new Exception('请求 TOKEN 格式错误！');
            }
            [$class, $appid, $time, $nostr, $sign] = [$data['class'], $data['appid'], $data['time'], $data['nostr'], $data['sign']];
            if (empty($class) || empty($appid) || empty($time) || empty($nostr) || empty($sign)) {
                throw new Exception('请求 TOKEN 格式异常！');
            }
            // 接口请求参数检查验证
            $auth = WechatAuth::mk()->where(['authorizer_appid' => $appid])->findOrEmpty();
            if ($auth->isEmpty()) {
                throw new Exception('该公众号还未授权，请重新授权！');
            }
            if (empty($auth['status'])) {
                throw new Exception('该公众号已被禁用，请联系管理员！');
            }
            if (!empty($auth['deleted'])) {
                throw new Exception('该公众号已取消授权，请重新授权！');
            }
            if (abs(time() - $data['time']) > 3600) {
                throw new Exception('请求时间与服务器时差过大，请同步时间！');
            }
            if (md5("{$class}#{$appid}#{$auth['appkey']}#{$time}#{$nostr}") !== $sign) {
                throw new Exception("该公众号{$appid}请求签名异常！");
            }
            $auth->where(['id' => $auth->getAttr('id')])->inc('total')->update([]);
            return AuthService::__callStatic($class, [$appid]);
        } catch (\Exception $exception) {
            return new \Exception($exception->getMessage(), 404);
        }
    }
}
