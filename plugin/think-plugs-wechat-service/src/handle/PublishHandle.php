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

namespace plugin\wechat\service\handle;

use plugin\wechat\service\AuthService;
use think\admin\Service;
use WeChat\Exceptions\InvalidDecryptException;
use WeChat\Exceptions\InvalidResponseException;
use WeChat\Exceptions\LocalCacheException;

/**
 * 授权公众上线测试处理
 * Class PublishHandle.
 */
class PublishHandle extends Service
{
    /**
     * 事件初始化.
     * @throws InvalidDecryptException
     * @throws InvalidResponseException
     * @throws LocalCacheException
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
                }
                [, $code] = explode(':', $receive['content'], 2);
                AuthService::WeOpenService()->getQueryAuthorizerInfo($code);
                AuthService::WeChatCustom($appid)->send([
                    'touser' => $wechat->getOpenid(), 'msgtype' => 'text', 'text' => [
                        'content' => "{$code}_from_api",
                    ],
                ]);
                return 'success';
            case 'event':
                return $wechat->text("{$receive['event']}from_callback")->reply([], true);
            default:
                return 'success';
        }
    }
}
