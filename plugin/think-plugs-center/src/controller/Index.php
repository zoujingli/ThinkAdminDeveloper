<?php

// +----------------------------------------------------------------------
// | Center Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-center
// | github 代码仓库：https://github.com/zoujingli/think-plugs-center
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\center\controller;

use plugin\center\Service;
use plugin\center\service\Plugin;
use think\admin\Controller;
use think\admin\service\AdminService;
use think\Response;

/**
 * 应用插件管理
 * Class Index
 * @package plugin\center\controller
 */
class Index extends Controller
{
    /**
     * 应用插件入口
     * @menu true
     * @login true
     * @return void|\think\Response
     * @throws \think\admin\Exception
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
     * 显示插件菜单
     * @login true
     * @param string $encode 应用插件编码
     * @throws \ReflectionException
     * @throws \think\admin\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function layout(string $encode = '')
    {
        if (empty($code = decode($encode))) {
            $this->fetchError('应用插件不能为空！');
        }
        sysvar('CurrentPluginCode', $code);
        $this->plugin = \think\admin\Plugin::get($code);
        if (empty($this->plugin)) $this->fetchError('插件未安装！');

        // 读取插件菜单
        $menus = $this->plugin['service']::menu();
        if (empty($menus)) $this->fetchError('插件未配置菜单！');

        foreach ($menus as $k1 => &$one) {
            $one['id'] = $k1 + 1;
            $one['url'] = $one['url'] ?? (empty($one['node']) ? '#' : plguri($one['node']));
            $one['title'] = lang($one['title'] ?? $one['name']);
            if (!empty($one['subs'])) {
                foreach ($one['subs'] as $k2 => &$two) {
                    if (isset($two['node']) && !auth($two['node'])) {
                        unset($one['subs'][$k2]);
                        continue;
                    }
                    $two['id'] = intval($k2) + 1;
                    $two['pid'] = $one['id'];
                    $two['url'] = empty($two['node']) ? '#' : plguri($two['node']);
                    $two['title'] = lang($two['title'] ?? $two['name']);
                }
                $one['sub'] = $one['subs'];
                unset($one['subs']);
            }
            if ($one['url'] === '#' && empty($one['sub']) || (isset($one['node']) && !auth($one['node']))) {
                unset($menus[$k1]);
            }
        }

        /*! 读取当前用户权限菜单树 */
        $this->menus = [
            [
                'id'    => 9999998,
                'url'   => '#',
                'sub'   => $menus,
                'node'  => Service::getAppCode(),
                'title' => $this->plugin['name']
            ],
        ];
        // 如果插件数量大于1，显示返回插件列表
        if (count(Plugin::getLocalPlugs('module', true)) > 1) {
            $this->menus[] = [
                'id'    => 9999999,
                'url'   => admuri('index/index', ['from' => 'force']),
                'node'  => 'plugin-center/index/index',
                'title' => '返回首页'
            ];
        }
        $this->super = AdminService::isSuper();
        $this->title = $this->plugin['name'] ?? '';
        $this->theme = AdminService::getUserTheme();
        $this->fetch('layout/index');
    }

    /**
     * 设置默认插件
     * @auth true
     * @return void
     * @throws \think\admin\Exception
     */
    public function setDefault()
    {
        sysdata('plugin.center.config', $this->_vali([
            'default.require' => '默认插件不能为空！'
        ]));
        $this->success('设置默认插件成功！');
    }

    /**
     * 跳转到指定插件
     * @param string $code
     * @param string $name
     * @return \think\Response
     */
    private function openPlugin(string $code, string $name = '打开指定插件'): Response
    {
        $href = sysuri(sprintf('layout/%s', encode(sysvar('CurrentPluginCode', $code))), [], false);
        return json(['code' => 1, 'info' => $name, 'data' => $href, 'wait' => 'false']);
    }

    /**
     * 显示异常模板
     * @return void
     */
    private function fetchError(string $content)
    {
        $this->content = $content;
        $this->fetch('layout/error');
    }
}