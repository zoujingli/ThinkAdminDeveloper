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

namespace plugin\wuma;

use plugin\wuma\command\Create;
use think\admin\Plugin;

/**
 * 插件注册服务
 * @class Service
 */
class Service extends Plugin
{
    protected $appName = '防伪溯源';

    protected $package = 'zoujingli/think-plugs-wuma';

    public function register(): void
    {
        $this->commands([Create::class]);
        // 注册全局防伪访问路由
        $this->app->route->any('<mode>/<code>!<verify><extra?>', Query::class . '@index')->pattern([
            'mode' => 'c|n|m', 'code' => '[0-9a-zA-Z]+', 'verify' => '[0-9]{4}', 'extra' => '.+',
        ]);
    }

    public static function menu(): array
    {
        $code = app(static::class)->appCode;
        return [
            [
                'name' => '物码管理',
                'subs' => [
                    ['name' => '物码批次管理', 'icon' => 'layui-icon layui-icon-app', 'node' => "{$code}/code/index"],
                ],
            ],
            [
                'name' => '防伪溯源',
                'subs' => [
                    ['name' => '溯源模板管理', 'icon' => 'layui-icon layui-icon-template-1', 'node' => "{$code}/source.template/index"],
                    ['name' => '生产批次管理', 'icon' => 'layui-icon layui-icon-diamond', 'node' => "{$code}/source.produce/index"],
                    ['name' => '赋码批次管理', 'icon' => 'layui-icon layui-icon-templeate-1', 'node' => "{$code}/source.assign/index"],
                    ['name' => '区块链授权证书', 'icon' => 'layui-icon layui-icon-vercode', 'node' => "{$code}/source.certificate/index"],
                    ['name' => '区块链内容管理', 'icon' => 'layui-icon layui-icon-vercode', 'node' => "{$code}/source.blockchain/index"],
                ],
            ],
            [
                'name' => '窜货监控',
                'subs' => [
                    ['name' => '扫码查询管理(开发中)', 'icon' => 'layui-icon layui-icon-template', 'node' => "{$code}/scaner.query/index"],
                    ['name' => '扫码明细管理(开发中)', 'icon' => 'layui-icon layui-icon-template', 'node' => "{$code}/scaner.notify/index"],
                    ['name' => '窜货代理管理(开发中)', 'icon' => 'layui-icon layui-icon-template', 'node' => "{$code}/scaner.notify/agent"],
                    ['name' => '窜货区域管理(开发中)', 'icon' => 'layui-icon layui-icon-location', 'node' => "{$code}/scaner.notify/area"],
                    ['name' => '数据实时监测(开发中)', 'icon' => 'layui-icon layui-icon-chart', 'node' => "{$code}/scaner.protal/index"],
                ],
            ],
            [
                'name' => '库存调度',
                'subs' => [
                    ['name' => '总部仓库管理', 'icon' => 'layui-icon layui-icon-component', 'node' => "{$code}/warehouse/index"],
                    ['name' => '仓库账号管理', 'icon' => 'layui-icon layui-icon-user', 'node' => "{$code}/warehouse.user/index"],
                    ['name' => '入库数据管理', 'icon' => 'layui-icon layui-icon-template', 'node' => "{$code}/warehouse.inter/index"],
                    ['name' => '出库数据管理', 'icon' => 'layui-icon layui-icon-template', 'node' => "{$code}/warehouse.outer/index"],
                    ['name' => '仓库库存统计', 'icon' => 'layui-icon layui-icon-theme', 'node' => "{$code}/warehouse.stock/index"],
                    ['name' => '标签关联管理', 'icon' => 'layui-icon layui-icon-senior', 'node' => "{$code}/warehouse.relation/index"],
                    ['name' => '标签替换管理', 'icon' => 'layui-icon layui-icon-slider', 'node' => "{$code}/warehouse.replace/index"],
                    ['name' => '仓库标签历史', 'icon' => 'layui-icon layui-icon-console', 'node' => "{$code}/warehouse.history/index"],
                ],
            ],
            [
                'name' => '代理管理',
                'subs' => [
                    ['name' => '平台参数配置(开发中)', 'icon' => 'layui-icon layui-icon-component', 'node' => "{$code}/sales.config/index"],
                    ['name' => '代理等级管理', 'icon' => 'layui-icon layui-icon-component', 'node' => "{$code}/sales.level/index"],
                    ['name' => '代理用户管理', 'icon' => 'layui-icon layui-icon-component', 'node' => "{$code}/sales.user/index"],
                    ['name' => '代理库存管理', 'icon' => 'layui-icon layui-icon-component', 'node' => "{$code}/sales.stock/index"],
                    ['name' => '标签流转历史(开发中)', 'icon' => 'layui-icon layui-icon-component', 'node' => "{$code}/sales.history/index"],
                    ['name' => '调货记录管理', 'icon' => 'layui-icon layui-icon-component', 'node' => "{$code}/sales.order/index"],
                ],
            ],
        ];
    }
}
