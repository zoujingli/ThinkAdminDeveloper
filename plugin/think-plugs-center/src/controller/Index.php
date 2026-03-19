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
use think\Response;

/**
 * 应用插件管理
 * Class Index.
 */
class Index extends Controller
{
    /**
     * 应用插件入口.
     * @menu true
     * @login true
     * @return Response|void
     * @throws Exception
     */
    public function index()
    {
        // 读取有菜单的插件列表
        $this->items = Plugin::getLocalPlugs('module', true);
        $this->codes = array_column($this->items, 'code');
        $this->default = sysdata('plugin.center.config')['default'] ?? '';
        if ($this->request->get('from') !== 'force') {
            // 检查默认插件并自动跳转
            if (in_array($this->default, $this->codes)) {
                return $this->openPlugin($this->default, '打开默认插件');
            }
            // 只有一个插件则自动进入插件
            if (count($this->codes) === 1) {
                return $this->openPlugin(array_pop($this->codes), '打开指定插件');
            }
        }
        // 显示插件列表
        $this->fetch();
    }

    /**
     * 显示插件菜单.
     * @login true
     * @param string $encode 应用插件编码
     * @throws \ReflectionException
     * @throws Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function layout(string $encode = '')
    {
        if (empty($code = decode($encode))) {
            $this->fetchError('应用插件不能为空！');
        }
        AppService::activatePlugin($code);
        $this->plugin = \think\admin\Plugin::get($code);
        if (empty($this->plugin)) {
            $this->fetchError('插件未安装！');
        }

        // 读取插件菜单
        $menus = AppService::menus($this->plugin, true, true);
        if (empty($menus)) {
            $this->fetchError('插件未配置菜单！');
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

        /* ! 读取当前用户权限菜单树 */
        $this->menus = [
            [
                'id' => 9999998,
                'url' => '#',
                'sub' => $menus,
                'node' => Service::getAppCode(),
                'title' => $this->plugin['name'],
            ],
        ];
        // 如果插件数量大于1，显示返回插件列表
        if (count(Plugin::getLocalPlugs('module', true)) > 1) {
            $this->menus[] = [
                'id' => 9999999,
                'url' => system_uri('index/index', ['from' => 'force']),
                'node' => 'plugin-center/index/index',
                'title' => '返回首页',
            ];
        }
        $this->super = SystemAuthService::isSuper();
        $this->title = $this->plugin['name'] ?? '';
        $this->theme = SystemAuthService::getUserTheme();
        $this->fetch('layout/index');
    }

    /**
     * 设置默认插件.
     * @auth true
     * @throws Exception
     */
    public function setDefault()
    {
        sysdata('plugin.center.config', $this->_vali([
            'default.require' => '默认插件不能为空！',
        ]));
        $this->success('设置默认插件成功！');
    }

    /**
     * 跳转到指定插件.
     */
    private function openPlugin(string $code, string $name = '打开指定插件'): Response
    {
        $current = AppService::current();
        $href = '#' . sysuri(sprintf('layout/%s', encode(strval($current['code'] ?? $code))), [], false);
        return json(['code' => 1, 'info' => $name, 'data' => $href, 'wait' => 'false']);
    }

    /**
     * 显示异常模板
     */
    private function fetchError(string $content)
    {
        $this->content = $content;
        $this->fetch('layout/error');
    }
}
