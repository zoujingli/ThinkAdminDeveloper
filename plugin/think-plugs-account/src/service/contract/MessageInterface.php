<?php

// +----------------------------------------------------------------------
// | Account Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-account
// | github 代码仓库：https://github.com/zoujingli/think-plugs-account
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\account\service;

namespace plugin\account\service\contract;

/**
 * 通用短信接口类
 * @class MessageInterface
 * @package plugin\account\service\contract
 */
interface MessageInterface
{
    /**
     * 初始化短信通道
     * @return static
     * @throws \think\admin\Exception
     */
    public function init(array $config = []): MessageInterface;

    /**
     * 发送短信内容
     * @param string $code 短信模板CODE
     * @param string $phone 接收手机号码
     * @param array $params 短信模板变量
     * @param array $options 其他配置参数
     * @return array
     * @throws \think\admin\Exception
     */
    public function send(string $code, string $phone, array $params = [], array $options = []): array;

    /**
     * 发送短信验证码
     * @param string $scene 业务场景
     * @param string $phone 手机号码
     * @param array $params 模板变量
     * @param array $options 其他配置
     * @return array
     */
    public function verify(string $scene, string $phone, array $params = [], array $options = []): array;

    /**
     * 获取区域配置
     * @return array
     */
    public static function regions(): array;
}