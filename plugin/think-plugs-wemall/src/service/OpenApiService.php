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

namespace plugin\wemall\service;

use think\admin\Exception;
use think\admin\extend\HttpClient;
use think\admin\helper\ValidateHelper;
use think\admin\service\Service;
use think\App;
use think\exception\HttpResponseException;

/**
 * 商城开放平台接口服务。
 * @class OpenApiService
 */
class OpenApiService extends Service
{
    /**
     * 输出格式。
     * @var string
     */
    private $type = 'json';

    /**
     * 接口认证账号。
     * @var string
     */
    private $appid;

    /**
     * 接口认证密钥。
     * @var string
     */
    private $appkey;

    /**
     * 接口网关地址。
     * @var string
     */
    private $gateway;

    /**
     * 初始化接口配置。
     * @throws Exception
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->appid = sysconf('data.interface_appid|raw') ?: '';
        $this->appkey = sysconf('data.interface_appkey|raw') ?: '';
        $this->gateway = sysconf('data.interface_gateway|raw') ?: '';
    }

    /**
     * 设置接口网关。
     * @param string $gateway 接口网关地址
     * @return $this
     */
    public function gateway(string $gateway): self
    {
        $this->gateway = $gateway;
        return $this;
    }

    /**
     * 设置接口认证参数。
     * @param string $appid 接口账号
     * @param string $appkey 接口密钥
     * @return $this
     */
    public function setAuth(string $appid, string $appkey): self
    {
        $this->appid = $appid;
        $this->appkey = $appkey;
        return $this;
    }

    /**
     * 设置输出类型为 JSON。
     * @return $this
     */
    public function setOutTypeJson(): self
    {
        $this->type = 'json';
        return $this;
    }

    /**
     * 设置输出类型为数组。
     * @return $this
     */
    public function setOutTypeArray(): self
    {
        $this->type = 'array';
        return $this;
    }

    /**
     * 获取当前 APPID。
     */
    public function getAppid(): string
    {
        return $this->appid ?: '';
    }

    /**
     * 获取并校验请求数据。
     */
    public function getData(): array
    {
        $input = ValidateHelper::instance()->init([
            'time.require' => lang('请求参数 %s 不能为空！', ['time']),
            'sign.require' => lang('请求参数 %s 不能为空！', ['sign']),
            'data.require' => lang('请求参数 %s 不能为空！', ['data']),
            'appid.require' => lang('请求参数 %s 不能为空！', ['appid']),
            'nostr.require' => lang('请求参数 %s 不能为空！', ['nostr']),
        ], 'post', [$this, 'baseError']);

        $build = $this->signString($input['data'], $input['time'], $input['nostr']);
        if ($build['sign'] !== $input['sign']) {
            $this->baseError(lang('接口签名验证失败！'));
        }
        if (abs(intval($input['time']) - time()) > 30) {
            $this->baseError(lang('接口请求时差过大！'));
        }
        return json_decode($input['data'], true) ?: [];
    }

    /**
     * 回复业务异常消息。
     * @param mixed $info 消息内容
     * @param mixed $data 返回数据
     * @param mixed $code 业务状态码
     */
    public function error($info, $data = '{-null-}', $code = 0): void
    {
        if ($data === '{-null-}') {
            $data = new \stdClass();
        }
        $this->baseResponse(lang('请求响应异常！'), [
            'code' => $code, 'info' => $info, 'data' => $data,
        ]);
    }

    /**
     * 回复业务成功消息。
     * @param mixed $info 消息内容
     * @param mixed $data 返回数据
     * @param mixed $code 业务状态码
     */
    public function success($info, $data = '{-null-}', $code = 1): void
    {
        if ($data === '{-null-}') {
            $data = new \stdClass();
        }
        $this->baseResponse(lang('请求响应成功！'), [
            'code' => $code, 'info' => is_string($info) ? lang($info) : $info, 'data' => $data,
        ]);
    }

    /**
     * 回复根失败消息。
     * @param mixed $info 消息内容
     * @param mixed $data 返回数据
     * @param mixed $code 根状态码
     */
    public function baseError($info, $data = [], $code = 0): void
    {
        $this->baseResponse($info, $data, $code);
    }

    /**
     * 回复根成功消息。
     * @param mixed $info 消息内容
     * @param mixed $data 返回数据
     * @param mixed $code 根状态码
     */
    public function baseSuccess($info, $data = [], $code = 1): void
    {
        $this->baseResponse($info, $data, $code);
    }

    /**
     * 输出签名响应。
     * @param mixed $info 消息内容
     * @param mixed $data 返回数据
     * @param mixed $code 根状态码
     */
    public function baseResponse($info, $data = [], $code = 1): void
    {
        $array = $this->signData($data);
        throw new HttpResponseException(json([
            'code' => $code,
            'info' => $info,
            'time' => $array['time'],
            'sign' => $array['sign'],
            'appid' => $array['appid'],
            'nostr' => $array['nostr'],
            'data' => $this->type !== 'json' ? json_decode($array['data'], true) : $array['data'],
        ]));
    }

    /**
     * 执行接口请求。
     * @param string $uri 接口地址
     * @param array $data 请求数据
     * @param bool $check 是否校验响应签名
     * @throws Exception
     */
    public function doRequest(string $uri, array $data = [], bool $check = true): array
    {
        $url = rtrim($this->gateway, '/') . '/' . ltrim($uri, '/');
        $content = HttpClient::post($url, $this->signData($data)) ?: '';
        if (!($result = json_decode($content, true)) || empty($result)) {
            throw new Exception(lang('接口请求响应格式异常！'));
        }
        if (empty($result['code'])) {
            throw new Exception($result['info']);
        }
        $array = is_array($result['data']) ? $result['data'] : json_decode($result['data'], true);
        if (empty($check)) {
            return $array;
        }
        $json = is_string($result['data']) ? $result['data'] : json_encode($result['data'], JSON_UNESCAPED_UNICODE);
        $build = $this->signString($json, $result['time'], $result['nostr']);
        if ($build['sign'] === $result['sign']) {
            return $array ?: [];
        }
        throw new Exception(lang('返回结果签名验证失败！'));
    }

    /**
     * 对响应数据签名。
     * @param array $data 响应数据
     */
    private function signData(array $data): array
    {
        return $this->signString(json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 对 JSON 字符串签名。
     * @param string $json 待签名数据
     * @param mixed $time 时间戳
     * @param mixed $rand 随机串
     */
    private function signString(string $json, $time = null, $rand = null): array
    {
        $time = strval($time ?: time());
        $rand = strval($rand ?: md5(uniqid('', true)));
        $sign = md5("{$this->appid}#{$json}#{$time}#{$this->appkey}#{$rand}");
        return ['appid' => $this->appid, 'nostr' => $rand, 'time' => $time, 'sign' => $sign, 'data' => $json];
    }
}
