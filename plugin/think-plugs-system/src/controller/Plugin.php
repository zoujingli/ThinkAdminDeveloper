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

namespace plugin\system\controller;

use plugin\system\service\PluginCenterService;
use plugin\system\service\SystemAuthService;
use think\admin\Controller;
use think\admin\Exception;
use think\admin\service\AppService;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 系统插件中心控制器.
 * @class Plugin
 */
class Plugin extends Controller
{
    /**
     * 插件中心首页.
     *
     * @auth true
     * @menu true
     * @login true
     * @throws Exception
     */
    public function index(): void
    {
        if (!PluginCenterService::isEnabled()) {
            $this->title = '插件应用中心';
            $this->fetch('plugin/disabled');
            return;
        }

        $this->items = PluginCenterService::getLocalPlugs(true);
        $this->title = '插件应用中心';
        $this->fetch();
    }

    /**
     * 插件工作台布局
     *
     * @login true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws Exception
     * @throws ModelNotFoundException
     */
    public function layout(): void
    {
        if (!PluginCenterService::isEnabled()) {
            $this->fetchError('插件中心已禁用，请在系统参数中重新启用。');
            return;
        }

        $encode = strval($this->request->get('encode', ''));
        if (($code = strval(decode($encode))) === '') {
            $this->fetchError('插件编码不能为空。');
            return;
        }

        AppService::activatePlugin($code);
        $this->plugin = AppService::getPlugin($code, true);
        if (empty($this->plugin)) {
            $this->fetchError('插件未安装或未启用。');
            return;
        }

        $rawMenus = AppService::menus($this->plugin, false, true);
        if (empty($rawMenus)) {
            $this->fetchError('插件未配置菜单，无法进入工作台。');
            return;
        }

        $menus = AppService::menus($this->plugin, true, true);
        if (empty($menus)) {
            $this->fetchError('当前账号没有可用菜单，请联系管理员授权后再试。');
            return;
        }

        foreach ($menus as $k1 => &$one) {
            $one['id'] = $k1 + 1;
            if (!empty($one['subs'])) {
                foreach ($one['subs'] as $k2 => &$two) {
                    $two['id'] = $k2 + 1;
                    $two['pid'] = $one['id'];
                }
                $one['sub'] = $one['subs'];
                unset($one['subs']);
            }
        }
        unset($one, $two);

        $this->menus = [[
            'id' => 9999998,
            'url' => '#',
            'sub' => $menus,
            'node' => 'system/plugin/layout',
            'title' => $this->plugin['name'] ?? $code,
        ]];

        if (PluginCenterService::isMenuVisible()) {
            $this->menus[] = [
                'id' => 9999999,
                'url' => system_uri('system/plugin/index'),
                'node' => 'system/plugin/index',
                'title' => '返回插件中心',
            ];
        }

        $this->super = SystemAuthService::isSuper();
        $this->title = strval($this->plugin['name'] ?? '');
        $this->theme = SystemAuthService::getUserTheme();
        $this->tokenValueJson = json_encode(SystemAuthService::buildToken(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->fetch('plugin/layout');
    }

    /**
     * 渲染插件工作台错误页.
     */
    private function fetchError(string $content): void
    {
        $this->returnUrl = system_uri('system/plugin/index');
        $this->menus = [[
            'id' => 9999999,
            'url' => $this->returnUrl,
            'node' => 'system/plugin/index',
            'title' => '返回插件中心',
        ]];
        $this->super = SystemAuthService::isSuper();
        $this->theme = SystemAuthService::getUserTheme();
        $this->tokenValueJson = json_encode(SystemAuthService::buildToken(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->title = strval($this->plugin['name'] ?? '插件中心') . ' - 打开失败';
        $this->content = $content;
        $this->fetch('plugin/error');
    }
}
