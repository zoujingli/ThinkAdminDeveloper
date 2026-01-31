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

use think\admin\Controller;
use think\admin\extend\JwtExtend;
use think\exception\HttpResponseException;

abstract class Base extends Controller
{
    protected $body;

    protected $token;

    protected $device;

    /**
     * 返回失败的操作.
     * @param mixed $info 消息内容
     * @param mixed $data 返回数据
     * @param mixed $code 返回代码
     */
    public function error($info, $data = '{-null-}', $code = 0): void
    {
        if ($data === '{-null-}') {
            $data = new \stdClass();
        }
        throw new HttpResponseException(json([
            'code' => $code, 'info' => is_string($info) ? lang($info) : $info, 'data' => $data,
        ]));
    }

    /**
     * 返回成功的操作.
     * @param mixed $info 消息内容
     * @param mixed $data 返回数据
     * @param mixed $code 返回代码
     */
    public function success($info, $data = '{-null-}', $code = 1): void
    {
        if ($data === '{-null-}') {
            $data = new \stdClass();
        }
        $result = ['code' => $code, 'info' => is_string($info) ? lang($info) : $info, 'data' => $data];
        if (JwtExtend::isRejwt()) {
            $result['token'] = JwtExtend::token();
        }
        throw new HttpResponseException(json($result));
    }

    protected function initialize()
    {
        parent::initialize();
        // 读取请求令牌数据
        $this->token = $this->request->header('api-token', '');
        // 获取设备类型及序号
        $this->device = $this->_vali([
            'code.require' => '设备序号不能为空！',
            'type.require' => '设备类型不能为空！',
        ], [
            'code' => $this->request->header('api-code', ''),
            'type' => $this->request->header('api-type', ''),
        ]);
        // 读取请求数据
        $data = $this->_vali([
            'time.require' => '请求时间为空！',
            'type.require' => '加密类型为空！',
            'body.default' => '',
        ]);
        if (abs(time() - $data['time'] / 1000) > 60) {
            $this->error('请求时间过大！');
        }
        if (empty($data['body'])) {
            $this->body = [];
        } elseif (strtolower($data['type']) == 'aes') {
            $skey = md5("{$data['time']}.thinkadmin.top");
            $json = openssl_decrypt($data['body'], 'AES-256-CBC', $skey);
            $this->body = json_decode($json, true);
        } else {
            $this->body = json_decode($data['body'], true);
        }
    }
}
