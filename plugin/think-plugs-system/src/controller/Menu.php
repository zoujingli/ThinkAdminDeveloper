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

namespace plugin\system\controller;

use plugin\system\model\SystemMenu;
use plugin\system\service\MenuService;
use plugin\system\service\SystemAuthService;
use think\admin\Controller;
use think\admin\extend\ArrayTree;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 系统菜单管理.
 * @class Menu
 */
class Menu extends Controller
{
    /**
     * 系统菜单管理.
     * @auth true
     * @menu true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        $this->title = '系统菜单管理';
        $this->type = $this->get['type'] ?? 'index';
        $this->plugin = $this->resolvePlugin();
        $this->plugins = MenuService::getPlugins($this->app->isDebug());
        // 获取顶级菜单ID
        $this->pid = $this->get['pid'] ?? '';

        // 查询顶级菜单集合
        $this->menupList = MenuService::getRoots($this->plugin);

        SystemMenu::mQuery()->layTable();
    }

    /**
     * 添加系统菜单.
     * @auth true
     */
    public function add()
    {
        $this->_applyFormToken();
        SystemMenu::mForm('form');
    }

    /**
     * 编辑系统菜单.
     * @auth true
     */
    public function edit()
    {
        $this->_applyFormToken();
        SystemMenu::mForm('form');
    }

    /**
     * 修改菜单状态
     * @auth true
     */
    public function state()
    {
        SystemMenu::mSave($this->_vali([
            'status.in:0,1' => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除系统菜单.
     * @auth true
     */
    public function remove()
    {
        SystemMenu::mDelete();
    }

    /**
     * 列表数据处理.
     */
    protected function _index_page_filter(array &$data)
    {
        $data = MenuService::filterTree(ArrayTree::arr2tree($data), $this->plugin ?? null);
        // 回收站过滤有效菜单
        if ($this->type === 'recycle') {
            foreach ($data as $k1 => &$p1) {
                if (!empty($p1['sub'])) {
                    foreach ($p1['sub'] as $k2 => &$p2) {
                        if (!empty($p2['sub'])) {
                            foreach ($p2['sub'] as $k3 => $p3) {
                                if ($p3['status'] > 0) {
                                    unset($p2['sub'][$k3]);
                                }
                            }
                        }
                        if (empty($p2['sub']) && ($p2['url'] === '#' or $p2['status'] > 0)) {
                            unset($p1['sub'][$k2]);
                        }
                    }
                }
                if (empty($p1['sub']) && ($p1['url'] === '#' or $p1['status'] > 0)) {
                    unset($data[$k1]);
                }
            }
        }
        // 菜单数据树数据变平化
        $data = ArrayTree::arr2table($data);

        // 过滤非当前顶级菜单的下级菜单,并重新索引数组
        if ($this->type === 'index' && $this->pid) {
            $data = array_values(array_filter($data, function ($item) {
                return strpos($item['spp'], ",{$this->pid},") !== false;
            }));
        }

        foreach ($data as &$vo) {
            if ($vo['url'] !== '#' && !preg_match('/^(https?:)?(\/\/|\\\)/i', $vo['url'])) {
                $vo['url'] = trim(url($vo['url']) . ($vo['params'] ? "?{$vo['params']}" : ''), '\/');
            }
        }
    }

    /**
     * 表单数据处理.
     */
    protected function _form_filter(array &$vo)
    {
        if ($this->request->isGet()) {
            $debug = $this->app->isDebug();
            $this->plugins = MenuService::getPlugins($debug);
            $this->plugin = $this->resolvePlugin($vo);
            /* 清理权限节点 */
            $debug && SystemAuthService::clear();
            /* 读取系统功能节点 */
            $this->nodes = MenuService::getList($debug, $this->plugin);
            $this->auths = MenuService::getAuths($debug, $this->plugin);
            /* 选择自己上级菜单 */
            $vo['pid'] = $vo['pid'] ?? input('pid', '0');
            /* 列出可选上级菜单 */
            $this->menus = MenuService::getParents($this->plugin);
            if (isset($vo['id'])) {
                foreach ($this->menus as $menu) {
                    if ($menu['id'] === $vo['id']) {
                        $vo = $menu;
                    }
                }
            }
            if (isset($vo['spt'], $vo['spc']) && in_array($vo['spt'], [1, 2]) && $vo['spc'] > 0) {
                foreach ($this->menus as $key => $menu) {
                    if ($vo['spt'] <= $menu['spt']) {
                        unset($this->menus[$key]);
                    }
                }
            }
        }
    }

    /**
     * 解析当前插件上下文.
     */
    private function resolvePlugin(array $vo = []): string
    {
        if (!empty($plugin = trim(strval($this->request->get('plugin', ''))))) {
            return $plugin;
        }

        if (!empty($vo['node']) || !empty($vo['url'])) {
            return MenuService::detectPlugin($vo);
        }

        $id = intval($this->request->get('id', 0));
        if ($id > 0 && ($menu = SystemMenu::mk()->find($id))) {
            return MenuService::detectPlugin($menu->toArray());
        }

        $pid = intval($this->request->get('pid', 0));
        if ($pid > 0 && ($menu = SystemMenu::mk()->find($pid))) {
            return MenuService::detectPlugin($menu->toArray());
        }

        return '';
    }
}
