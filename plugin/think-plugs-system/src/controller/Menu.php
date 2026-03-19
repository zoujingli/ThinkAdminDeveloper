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
        // 菜单展示类型：index 为正常菜单，recycle 为回收站。
        $this->type = $this->get['type'] ?? 'index';
        // 顶级菜单筛选参数。
        $this->pid = $this->get['pid'] ?? '';
        // 顶级菜单快捷筛选列表。
        $this->menupList = MenuService::getRoots();
        SystemMenu::mQuery()->layTable();
    }

    /**
     * 添加系统菜单.
     * @auth true
     */
    public function add()
    {
        SystemMenu::mForm('form');
    }

    /**
     * 编辑系统菜单.
     * @auth true
     */
    public function edit()
    {
        SystemMenu::mForm('form');
    }

    /**
     * 修改菜单状态.
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
        // 菜单数据先转为树结构，便于回收站场景按层级过滤。
        $data = ArrayTree::arr2tree($data);

        // 回收站只展示真正被禁用的菜单节点。
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
                        if (empty($p2['sub']) && ($p2['url'] === '#' || $p2['status'] > 0)) {
                            unset($p1['sub'][$k2]);
                        }
                    }
                }
                if (empty($p1['sub']) && ($p1['url'] === '#' || $p1['status'] > 0)) {
                    unset($data[$k1]);
                }
            }
        }

        // 还原为表格结构给 LayUI 列表渲染。
        $data = ArrayTree::arr2table($data);

        // 按顶级菜单筛选当前视图需要展示的下级菜单。
        if ($this->type === 'index' && $this->pid) {
            $data = array_values(array_filter($data, function ($item) {
                return strpos($item['spp'], ",{$this->pid},") !== false;
            }));
        }

        // 内部路由补全为可直接访问的系统地址。
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
            // 调试模式下先清理权限缓存，确保节点与菜单实时同步。
            $debug && SystemAuthService::clear();
            // 读取当前可选节点与授权节点。
            $this->nodes = MenuService::getList($debug);
            $this->auths = MenuService::getAuths($debug);
            // 默认以上级菜单作为当前挂载位置。
            $vo['pid'] = $vo['pid'] ?? input('pid', '0');
            // 列出可选上级菜单。
            $this->menus = MenuService::getParents();
            if (isset($vo['id'])) {
                foreach ($this->menus as $menu) {
                    if ($menu['id'] === $vo['id']) {
                        $vo = $menu;
                    }
                }
            }

            // 已经存在层级关系时，过滤掉不可再挂载的父级节点。
            if (isset($vo['spt'], $vo['spc']) && in_array($vo['spt'], [1, 2], true) && $vo['spc'] > 0) {
                foreach ($this->menus as $key => $menu) {
                    if ($vo['spt'] <= $menu['spt']) {
                        unset($this->menus[$key]);
                    }
                }
            }
        }
    }
}
