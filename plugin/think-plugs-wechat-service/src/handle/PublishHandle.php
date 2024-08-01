<?php

// +----------------------------------------------------------------------
// | Wechat Service Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 Anyon <zoujingli@qq.com>
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
use think\admin\Service;

/**
 * 授权公众上线测试处理
 * Class PublishHandle
 * @package plugin\wechat\service
 */
class PublishHandle extends Service
{
    /**
     * 事件初始化
     * @param string $appid
     * @return string
     * @throws \WeChat\Exceptions\InvalidDecryptException
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
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
        $receive = array_change_key_case($wechat->getReceive());
        switch (strtolower($wechat->getMsgType())) {
            case 'text':
                if ($receive['content'] === 'TESTCOMPONENT_MSG_TYPE_TEXT') {
                    return $wechat->text('TESTCOMPONENT_MSG_TYPE_TEXT_callback')->reply([], true);
                } else {
                    [, $code] = explode(':', $receive['content'], 2);
                    AuthService::WeOpenService()->getQueryAuthorizerInfo($code);
                    AuthService::WeChatCustom($appid)->send([
                        'touser' => $wechat->getOpenid(), 'msgtype' => 'text', 'text' => [
                            'content' => "{$code}_from_api",
                        ],
                    ]);
                    return 'success';
                }
            case 'event':
                return $wechat->text("{$receive['event']}from_callback")->reply([], true);
            default:
                return 'success';
        }
    }
}