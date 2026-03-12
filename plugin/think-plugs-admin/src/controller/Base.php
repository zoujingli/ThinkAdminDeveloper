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

namespace plugin\admin\controller;

use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\admin\model\SystemBase;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 数据字典管理.
 * @class Base
 */
class Base extends Controller
{
    /**
     * 数据字典管理.
     * @auth true
     * @menu true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        SystemBase::mQuery()->layTable(function () {
            $this->title = '数据字典管理';
            $this->types = SystemBase::types();
            $this->type = $this->get['type'] ?? ($this->types[0] ?? '-');
            $this->pluginGroups = SystemBase::groups($this->type);
        }, static function (QueryHelper $query) {
            $query->equal('type');
            $query->like('code,name,status')->dateBetween('create_time');
            if ($group = trim(strval(input('get.plugin_group', '')))) {
                $ids = SystemBase::idsByPluginGroup($group, strval(input('get.type', '')));
                empty($ids) ? $query->whereRaw('1 = 0') : $query->whereIn('id', $ids);
            }
        });
    }

    /**
     * 添加数据字典.
     * @auth true
     */
    public function add()
    {
        SystemBase::mForm('form');
    }

    /**
     * 编辑数据字典.
     * @auth true
     */
    public function edit()
    {
        SystemBase::mForm('form');
    }

    /**
     * 修改数据状态
     * @auth true
     */
    public function state()
    {
        SystemBase::mSave($this->_vali([
            'status.in:0,1' => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除数据记录.
     * @auth true
     */
    public function remove()
    {
        SystemBase::mDelete();
    }

    /**
     * 表单数据处理.
     * @throws DbException
     */
    protected function _form_filter(array &$data)
    {
        if ($this->request->isGet()) {
            $this->types = SystemBase::types();
            $this->types[] = '--- ' . lang('新增类型') . ' ---';
            $this->type = $this->get['type'] ?? ($this->types[0] ?? '-');
            $meta = SystemBase::parseContent(strval($data['content'] ?? ''));
            $this->plugins = SystemBase::pluginOptions();
            $codes = (array)($meta['plugin'] ?: $meta['plugins']);
            $this->pluginCode = count($codes) === 1 ? strval(current($codes)) : '';
            $this->contentText = strval($meta['text'] ?? ($data['content'] ?? ''));
        } else {
            $data['content'] = SystemBase::packContent(
                strval($data['content_text'] ?? ''),
                $data['plugin_code'] ?? ''
            );
            unset($data['content_text'], $data['plugin_code']);
            $exists = SystemBase::mk()
                ->where(['code' => $data['code'], 'type' => $data['type']])
                ->where('id', '<>', $data['id'] ?? 0)
                ->count();
            if ($exists > 0) {
                $this->error('数据编码已经存在！');
            }
        }
    }

    /**
     * 列表数据处理.
     */
    protected function _page_filter(array &$data)
    {
        $data = SystemBase::appendPlugins($data);
    }
}
