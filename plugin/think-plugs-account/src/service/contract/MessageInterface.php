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

namespace plugin\account\service;

namespace plugin\account\service\contract;

use think\admin\Exception;

/**
 * 通用短信接口类.
 * @class MessageInterface
 */
interface MessageInterface
{
    /**
     * 初始化短信通道.
     * @return static
     * @throws Exception
     */
    public function init(array $config = []): MessageInterface;

    /**
     * 发送短信内容.
     * @param string $code 短信模板CODE
     * @param string $phone 接收手机号码
     * @param array $params 短信模板变量
     * @param array $options 其他配置参数
     * @throws Exception
     */
    public function send(string $code, string $phone, array $params = [], array $options = []): array;

    /**
     * 发送短信验证码
     * @param string $scene 业务场景
     * @param string $phone 手机号码
     * @param array $params 模板变量
     * @param array $options 其他配置
     */
    public function verify(string $scene, string $phone, array $params = [], array $options = []): array;

    /**
     * 获取区域配置.
     */
    public static function regions(): array;
}
