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

namespace plugin\wechat\service\service;

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
