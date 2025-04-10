<?php

// +----------------------------------------------------------------------
// | Wechat Service Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 Anyon <zoujingli@qq.com>
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wechat-service
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wechat-service
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\wechat\service\handle;

use plugin\wechat\service\AuthService;
use plugin\wechat\service\model\WechatAuth;
use think\admin\extend\HttpExtend;
use think\admin\Service;

/**
 * 授权公众号消息转发处理
 * Class ReceiveHandle
 * @package plugin\wechat\service
 */
class ReceiveHandle extends Service
{
    /**
     * 事件初始化
     * @param string $appid
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function handler(string $appid): string
    {
        try {
            $wechat = AuthService::WeChatReceive($appid);
        } catch (\Exception $exception) {
            $message = "Wechat {$appid} message handling failed, {$exception->getMessage()}";
            $this->app->log->notice($message);
            return $message;
        }
        // 验证微信配置信息
        $config = WechatAuth::mk()->where(['authorizer_appid' => $appid])->find();
        if (empty($config) || empty($config['appuri'])) {
            $this->app->log->notice("Authorization verification of wechat {$appid} interface failed. You need to rebind authorization");
        } else try {
            [$data, $openid] = [$wechat->getReceive(), $wechat->getOpenid()];
            if (isset($data['EventKey']) && is_object($data['EventKey'])) $data['EventKey'] = (array)$data['EventKey'];
            $params = ['appid' => $appid, 'openid' => $openid, 'params' => json_encode($data)];
            [$params['receive'], $params['encrypt']] = [serialize($data), intval($wechat->isEncrypt())];
            if (is_string($result = HttpExtend::post($config['appuri'], $params, ['timeout' => 30]))) {
                $json = json_decode($result = ltrim($result, "\XEF\XBB\XBF"), true);
                return is_array($json) ? $wechat->reply($json, true, $wechat->isEncrypt()) : $result;
            }
        } catch (\Exception $exception) {
            $this->app->log->notice("Wechat {$appid} message push processing exception，{$exception->getMessage()}");
        }
        return 'success';
    }
}