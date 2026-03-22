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
use think\Response;

/**
 * 系统插件中心控制器
 * @class Plugin
 */
class Plugin extends Controller
{
    /**
     * 插件中心首页
     * @auth true
     * @menu true
     * @login true
     * @return Response|void
     * @throws Exception
     */
    public function index()
    {
        if (!PluginCenterService::isEnabled()) {
            $this->title = '插件中心';
            $this->fetch('plugin/disabled');
            return;
        }

        $this->items = PluginCenterService::getLocalPlugs(true);
        $this->codes = array_column($this->items, 'code');
        $this->default = PluginCenterService::getDefaultApp();

        if ($this->request->get('from') !== 'force') {
            if (in_array($this->default, $this->codes, true)) {
                return $this->openPlugin($this->default, '打开默认插件');
            }
            if (count($this->codes) === 1) {
                return $this->openPlugin(array_pop($this->codes), '打开唯一插件');
            }
        }

        $this->title = '插件中心';
        $this->fetch();
    }

    /**
     * 插件工作台布局
     * @login true
     * @throws Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function layout()
    {
        if (!PluginCenterService::isEnabled()) {
            $this->fetchError('插件中心已禁用，请在系统参数中重新启用。');
            return;
        }

        $encode = strval($this->request->get('encode', ''));
        if (empty($code = decode($encode))) {
            $this->fetchError('插件编码不能为空。');
            return;
        }

        AppService::activatePlugin($code);
        $this->plugin = AppService::get($code, true);
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

        if (PluginCenterService::isMenuVisible() && count(PluginCenterService::getLocalPlugs(true)) > 1) {
            $this->menus[] = [
                'id' => 9999999,
                'url' => system_uri('system/plugin/index', ['from' => 'force']),
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
     * 保存默认插件入口
     * @auth true
     * @throws Exception
     */
    public function setDefaultApp()
    {
        $default = trim(strval($this->request->post('default', '')));
        if ($default !== '' && !PluginCenterService::hasSelectableApp($default)) {
            $this->error('默认插件不存在或当前不可用。');
        }

        PluginCenterService::setConfig(['default' => $default]);
        $this->success($default === '' ? '已取消默认插件入口。' : '默认插件入口保存成功。');
    }

    /**
     * 打开目标插件
     */
    private function openPlugin(string $code, string $info): Response
    {
        $href = '#' . sysuri('system/plugin/layout', ['encode' => encode($code)], false);
        return json(['code' => 1, 'info' => $info, 'data' => $href, 'wait' => 'false']);
    }

    /**
     * 渲染插件工作台错误页
     */
    private function fetchError(string $content): void
    {
        $this->returnUrl = system_uri('system/plugin/index', ['from' => 'force']);
        $this->menus = [[
            'id' => 9999999,
            'url' => $this->returnUrl,
            'node' => 'system/plugin/index',
            'title' => '返回插件中心',
        ]];
        $this->super = SystemAuthService::isSuper();
        $this->theme = SystemAuthService::getUserTheme();
        $this->tokenValueJson = json_encode(SystemAuthService::buildToken(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->title = (strval($this->plugin['name'] ?? '插件中心')) . ' - 打开失败';
        $this->content = $content;
        $this->fetch('plugin/error');
    }
}
