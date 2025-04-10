<?php

// +----------------------------------------------------------------------
// | WeMall Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wemall
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wemall
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\wemall\service;

/**
 * 商城配置服务
 * @class ConfigService
 * @package plugin\wemall\service
 */
abstract class ConfigService
{

    /**
     * 商城配置缓存名
     * @var string
     */
    private static $skey = 'plugin.wemall.config';

    /**
     * 页面类型配置
     * @var string[]
     */
    public static $pageTypes = [
        'user_privacy'   => '用户隐私政策',
        'user_agreement' => '用户使用协议',
    ];

    /**
     * 读取商城配置参数
     * @param string|null $name
     * @param $default
     * @return array|mixed|null
     * @throws \think\admin\Exception
     */
    public static function get(?string $name = null, $default = null)
    {
        $syscfg = sysvar(self::$skey) ?: sysvar(self::$skey, sysdata(self::$skey));
        if (empty($syscfg['base_domain'])) $syscfg['base_domain'] = sysconf('base.site_host') . '/h5';
        return is_null($name) ? $syscfg : ($syscfg[$name] ?? $default);
    }

    /**
     * 保存商城配置参数
     * @param array $data
     * @return mixed
     * @throws \think\admin\Exception
     */
    public static function set(array $data)
    {
        // 修改前端域名处理
        if (!empty($data['base_domain'])) {
            $data['base_domain'] = rtrim($data['base_domain'], '\\/');
        }
        // 自动处理减免金额范围
        if (!empty($data['enable_reduct'])) {
            $reducts = [floatval($data['reduct_min'] ?? 0), floatval($data['reduct_max'] ?? 0)];
            [$data['reduct_min'], $data['reduct_max']] = [min($reducts), max($reducts)];
        }
        return sysdata(self::$skey, $data);
    }

    /**
     * 设置页面数据
     * @param string $code 页面编码
     * @param array $data 页面内容
     * @return mixed
     * @throws \think\admin\Exception
     */
    public static function setPage(string $code, array $data)
    {
        return sysdata("plugin.wemall.page.{$code}", $data);
    }

    /**
     * 获取页面内容
     * @param string $code
     * @return array
     * @throws \think\admin\Exception
     */
    public static function getPage(string $code): array
    {
        return sysdata("plugin.wemall.page.{$code}");
    }
}