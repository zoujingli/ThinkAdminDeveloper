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

/**
 * 商城配置服务
 * @class ConfigService
 */
abstract class ConfigService
{
    /**
     * 页面类型配置.
     * @var string[]
     */
    public static $pageTypes = [
        'user_privacy' => '用户隐私政策',
        'user_agreement' => '用户使用协议',
    ];

    /**
     * 商城配置缓存名.
     * @var string
     */
    private static $skey = 'plugin.wemall.config';

    /**
     * 读取商城配置参数.
     * @param null|mixed $default
     * @return null|array|mixed
     * @throws Exception
     */
    public static function get(?string $name = null, $default = null)
    {
        $syscfg = sysvar(self::$skey) ?: sysvar(self::$skey, sysdata(self::$skey));
        if (empty($syscfg['base_domain'])) {
            $syscfg['base_domain'] = sysconf('base.site_host') . '/h5';
        }
        return is_null($name) ? $syscfg : ($syscfg[$name] ?? $default);
    }

    /**
     * 保存商城配置参数.
     * @return mixed
     * @throws Exception
     */
    public static function set(array $data)
    {
        // 修改前端域名处理
        if (!empty($data['base_domain'])) {
            $data['base_domain'] = rtrim($data['base_domain'], '\/');
        }
        // 自动处理减免金额范围
        if (!empty($data['enable_reduct'])) {
            $reductMin = strval($data['reduct_min'] ?? '0.00');
            $reductMax = strval($data['reduct_max'] ?? '0.00');
            $data['reduct_min'] = strval(min($reductMin, $reductMax));
            $data['reduct_max'] = strval(max($reductMin, $reductMax));
        }
        return sysdata(self::$skey, $data);
    }

    /**
     * 设置页面数据.
     * @param string $code 页面编码
     * @param array $data 页面内容
     * @return mixed
     * @throws Exception
     */
    public static function setPage(string $code, array $data)
    {
        return sysdata("plugin.wemall.page.{$code}", $data);
    }

    /**
     * 获取页面内容.
     * @throws Exception
     */
    public static function getPage(string $code): array
    {
        return sysdata("plugin.wemall.page.{$code}");
    }
}
