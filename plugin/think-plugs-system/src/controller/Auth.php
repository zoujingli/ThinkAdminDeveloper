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

use plugin\system\service\SystemAuthService;
use plugin\system\model\SystemAuth;
use plugin\system\model\SystemNode;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\admin\service\AppService;
use think\admin\service\PluginService;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 系统权限管理.
 * @class Auth
 */
class Auth extends Controller
{
    /**
     * 系统权限管理.
     * @auth true
     * @menu true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        SystemAuth::mQuery()->layTable(function () {
            $this->title = '系统权限管理';
            $this->authGroups = SystemAuth::groups();
        }, static function (QueryHelper $query) {
            $query->like('title,desc')->equal('status,utype')->dateBetween('create_time');
            if ($group = trim(strval(input('get.plugin_group', '')))) {
                $ids = SystemAuth::idsByPluginGroup($group);
                empty($ids) ? $query->whereRaw('1 = 0') : $query->whereIn('id', $ids);
            }
        });
    }

    /**
     * 修改权限状态
     * @auth true
     */
    public function state()
    {
        SystemAuth::mSave($this->_vali([
            'status.in:0,1' => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除系统权限.
     * @auth true
     */
    public function remove()
    {
        SystemAuth::mDelete();
    }

    /**
     * 添加系统权限.
     * @auth true
     */
    public function add()
    {
        SystemAuth::mForm('form');
    }

    /**
     * 编辑系统权限.
     * @auth true
     */
    public function edit()
    {
        SystemAuth::mForm('form');
    }

    /**
     * 表单后置数据处理.
     */
    protected function _form_filter(array $data)
    {
        if ($this->request->isGet()) {
            $this->title = empty($data['title']) ? '添加访问授权' : "编辑【{$data['title']}】授权";
            $this->plugin = trim(strval($this->request->get('plugin', '')));
        } elseif ($this->request->post('action') === 'json') {
            if ($this->app->isDebug()) {
                SystemAuthService::clear();
            }
            $ztree = SystemAuthService::getTree(empty($data['id']) ? [] : SystemNode::mk()->where(['auth' => $data['id']])->column('node'));
            usort($ztree, static function ($a, $b) {
                if (explode('-', $a['node'])[0] !== explode('-', $b['node'])[0]) {
                    if (stripos($a['node'], 'plugin-') === 0) {
                        return 1;
                    }
                }
                return $a['node'] === $b['node'] ? 0 : ($a['node'] > $b['node'] ? 1 : -1);
            });
            $this->normalizePluginTree($ztree);
            $this->success('获取权限节点成功！', $ztree);
        } elseif (empty($data['nodes'])) {
            $this->error('未配置功能节点！');
        }
    }

    /**
     * 节点更新处理.
     */
    protected function _form_result(bool $state, array $post)
    {
        if ($state && $this->request->post('action') === 'save') {
            [$map, $data] = [['auth' => $post['id']], []];
            foreach ($post['nodes'] ?? [] as $node) {
                $data[] = $map + ['node' => $node];
            }
            SystemNode::mk()->where($map)->delete();
            count($data) > 0 && SystemNode::mk()->insertAll($data);
            sysoplog('系统权限管理', "配置系统权限[{$map['auth']}]授权成功");
            $this->success('权限修改成功！', 'javascript:history.back()');
        }
    }

    /**
     * 列表数据处理.
     */
    protected function _page_filter(array &$data)
    {
        $data = SystemAuth::appendPlugins($data);
    }

    /**
     * 标准化插件权限树标题与分组信息.
     * @param array<int, array<string, mixed>> $nodes
     */
    private function normalizePluginTree(array &$nodes, string $plugin = ''): void
    {
        foreach ($nodes as &$node) {
            $current = $plugin;
            if (strpos(strval($node['node'] ?? ''), '/') === false) {
                $current = strval($node['node'] ?? '');
                if ($pluginInfo = PluginService::resolve($current, true)) {
                    $node['title'] = lang(strval($pluginInfo['name'] ?? $node['title']));
                } elseif ($app = AppService::get($current)) {
                    $node['title'] = lang(strval($app['name'] ?? $node['title']));
                }
            }
            $node['plugin'] = $current;
            if (!empty($node['_sub_'])) {
                $this->normalizePluginTree($node['_sub_'], $current);
            }
        }
        unset($node);
    }
}
