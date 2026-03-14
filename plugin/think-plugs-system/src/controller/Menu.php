<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdmin
 * +----------------------------------------------------------------------
 * | Copyright 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | Official Website: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | Licensed ( https://mit-license.org )
 * | Disclaimer ( https://thinkadmin.top/disclaimer )
 * | VIP Introduce ( https://thinkadmin.top/vip-introduce )
 * +----------------------------------------------------------------------
 * | gitee repository https://gitee.com/zoujingli/ThinkAdmin
 * | github repository https://github.com/zoujingli/ThinkAdmin
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
 * System menu management.
 * @class Menu
 */
class Menu extends Controller
{
    /**
     * System menu management.
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
        $this->pid = $this->get['pid'] ?? '';
        $this->menupList = MenuService::getRoots();
        SystemMenu::mQuery()->layTable();
    }

    /**
     * Add menu.
     * @auth true
     */
    public function add()
    {
        $this->_applyFormToken();
        SystemMenu::mForm('form');
    }

    /**
     * Edit menu.
     * @auth true
     */
    public function edit()
    {
        $this->_applyFormToken();
        SystemMenu::mForm('form');
    }

    /**
     * Update menu status.
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
     * Remove menu.
     * @auth true
     */
    public function remove()
    {
        SystemMenu::mDelete();
    }

    /**
     * Filter table data.
     */
    protected function _index_page_filter(array &$data)
    {
        $data = ArrayTree::arr2tree($data);
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
        $data = ArrayTree::arr2table($data);

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
     * Filter form data.
     */
    protected function _form_filter(array &$vo)
    {
        if ($this->request->isGet()) {
            $debug = $this->app->isDebug();
            $debug && SystemAuthService::clear();
            $this->nodes = MenuService::getList($debug);
            $this->auths = MenuService::getAuths($debug);
            $vo['pid'] = $vo['pid'] ?? input('pid', '0');
            $this->menus = MenuService::getParents();
            if (isset($vo['id'])) {
                foreach ($this->menus as $menu) {
                    if ($menu['id'] === $vo['id']) {
                        $vo = $menu;
                    }
                }
            }
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
