<?php

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

namespace plugin\system;

use plugin\system\service\JwtTokenAuth;
use plugin\system\service\RbacAccess;
use plugin\system\service\SystemContext as PluginSystemContext;
use think\admin\Library;
use think\admin\Plugin;
use think\admin\runtime\RequestTokenService;
use think\admin\contract\SystemContextInterface;
use think\middleware\LoadLangPack;

/**
 * 插件服务注册.
 * @class Service
 */
class Service extends Plugin
{
    /**
     * 定义插件入口.
     * @var string
     */
    protected string $appCode = 'system';

    /**
     * 定义插件访问前缀.
     * @var string
     */
    protected string $appPrefix = 'system';

    /**
     * 定义插件名称.
     * @var string
     */
    protected string $appName = '系统管理';

    /**
     * 定义安装包名.
     * @var string
     */
    protected string $package = 'zoujingli/think-plugs-system';

    /**
     * 注册系统基础服务。
     */
    public function register(): void
    {
        Library::load(__DIR__ . '/common.php');
        $this->app->bind(SystemContextInterface::class, PluginSystemContext::class);
    }

    /**
     * 启动插件服务.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        $this->app->middleware->add(JwtTokenAuth::class);
        $isapi = RequestTokenService::authorizationToken($this->app->request) !== '';
        $agent = preg_replace('|\s+|', '', $this->app->request->header('user-agent', ''));
        $isrpc = is_numeric(stripos($agent, 'think-admin-jsonrpc')) || is_numeric(stripos($agent, 'PHPYarRPC'));
        if (empty($isapi) && empty($isrpc)) {
            $this->app->middleware->add(LoadLangPack::class);
        }
        $this->app->middleware->add(RbacAccess::class, 'route');
    }

    /**
     * 定义插件中心菜单.
     */
    public static function menu(): array
    {
        return [
            [
                'name' => '系统配置',
                'subs' => [
                    ['name' => '系统参数配置', 'icon' => 'layui-icon layui-icon-set', 'node' => 'system/config/index'],
                    ['name' => '系统任务管理', 'icon' => 'layui-icon layui-icon-log', 'node' => 'system/queue/index'],
                    ['name' => '系统日志管理', 'icon' => 'layui-icon layui-icon-form', 'node' => 'system/oplog/index'],
                    ['name' => '数据字典管理', 'icon' => 'layui-icon layui-icon-code-circle', 'node' => 'system/base/index'],
                    ['name' => '系统文件管理', 'icon' => 'layui-icon layui-icon-carousel', 'node' => 'system/file/index'],
                    ['name' => '系统菜单管理', 'icon' => 'layui-icon layui-icon-layouts', 'node' => 'system/menu/index'],
                ],
            ],
            [
                'name' => '权限管理',
                'subs' => [
                    ['name' => '系统权限管理', 'icon' => 'layui-icon layui-icon-vercode', 'node' => 'system/auth/index'],
                    ['name' => '系统用户管理', 'icon' => 'layui-icon layui-icon-username', 'node' => 'system/user/index'],
                ],
            ],
        ];
    }
}
