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

namespace plugin\center\controller;

use plugin\center\Service;
use plugin\center\service\Plugin;
use plugin\system\service\SystemAuthService;
use think\admin\Controller;
use think\admin\Exception;
use think\admin\service\AppService;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * Plugin layout page controller.
 * @class Layout
 */
class Layout extends Controller
{
    /**
     * Show plugin layout page.
     * @login true
     * @throws \ReflectionException
     * @throws Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        $encode = strval($this->request->get('encode', ''));
        if (empty($code = decode($encode))) {
            $this->fetchError('应用插件不能为空！');
            return;
        }

        AppService::activatePlugin($code);
        $this->plugin = AppService::get($code, true);
        if (empty($this->plugin)) {
            $this->fetchError('插件未安装！');
            return;
        }

        $rawMenus = AppService::menus($this->plugin, false, true);
        if (empty($rawMenus)) {
            $this->fetchError('插件未配置菜单！');
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
                    $two['id'] = intval($k2) + 1;
                    $two['pid'] = $one['id'];
                }
                $one['sub'] = $one['subs'];
                unset($one['subs']);
            }
        }

        $this->menus = [
            [
                'id' => 9999998,
                'url' => '#',
                'sub' => $menus,
                'node' => Service::getAppCode(),
                'title' => $this->plugin['name'],
            ],
        ];

        if (count(Plugin::getLocalPlugs(true)) > 1) {
            $this->menus[] = [
                'id' => 9999999,
                'url' => system_uri(Service::getAppCode() . '/index/index', ['from' => 'force']),
                'node' => 'center/index/index',
                'title' => '返回首页',
            ];
        }

        $this->super = SystemAuthService::isSuper();
        $this->title = $this->plugin['name'] ?? '';
        $this->theme = SystemAuthService::getUserTheme();
        $this->fetch('layout/index');
    }

    /**
     * Save default plugin app.
     * @auth true
     * @throws Exception
     */
    public function setDefaultApp()
    {
        sysdata('plugin.center.config', $this->_vali([
            'default.require' => '默认插件不能为空！',
        ]));
        $this->success('设置默认插件成功！');
    }

    /**
     * Show layout error template.
     */
    private function fetchError(string $content): void
    {
        $this->returnUrl = system_uri(Service::getAppCode() . '/index/index', ['from' => 'force']);
        $this->menus = [
            [
                'id' => 9999999,
                'url' => $this->returnUrl,
                'node' => 'center/index/index',
                'title' => '返回插件中心',
            ],
        ];
        $this->super = SystemAuthService::isSuper();
        $this->theme = SystemAuthService::getUserTheme();
        $this->title = ($this->plugin['name'] ?? '插件中心') . ' · 进入失败';
        $this->content = $content;
        $this->fetch('layout/error');
    }
}
