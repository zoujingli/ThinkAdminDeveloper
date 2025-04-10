<?php

// +----------------------------------------------------------------------
// | Wuma Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 收费插件 ( https://thinkadmin.top/fee-introduce.html )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wuma
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wuma
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\wuma\controller\source;

use plugin\wuma\model\PluginWumaSourceProduce;
use plugin\wuma\model\PluginWumaSourceTemplate;
use think\admin\Controller;
use think\admin\extend\CodeExtend;
use think\admin\helper\QueryHelper;

/**
 * 溯源模板管理
 * @class Template
 * @package plugin\wuma\controller\source
 */
class Template extends Controller
{
    /**
     * 溯源模板管理
     * @menu true
     * @auth true
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->type = $this->get['type'] ?? 'index';
        PluginWumaSourceTemplate::mQuery()->layTable(function () {
            $this->title = '溯源模板管理';
        }, function (QueryHelper $query) {
            $query->like('code,name')->like('tags', ',')->dateBetween('create_time');
            $query->where(['deleted' => 0, 'status' => intval($this->type === 'index')]);
        });
    }

    /**
     * 添加溯源模板
     * @auth true
     */
    public function add()
    {
        $this->title = '添加溯源模板';
        PluginWumaSourceTemplate::mForm('form');
    }

    /**
     * 编辑溯源模板
     * @auth true
     */
    public function edit()
    {
        $this->title = '编辑溯源模板';
        PluginWumaSourceTemplate::mForm('form');
    }

    /**
     * 复制溯源模板
     * @auth true
     * @return void
     */
    public function copy()
    {
        $this->title = '复制溯源模板';
        PluginWumaSourceTemplate::mForm('form');
    }

    /**
     * 复制表单处理
     * @param array $data
     * @return void
     */
    protected function _copy_form_filter(array &$data)
    {
        if ($this->request->isPost()) {
            $data['code'] = CodeExtend::uniqidDate(16, 'T');
            unset($data['id'], $data['create_time']);
        }
    }

    /**
     * 表单数据处理
     * @param array $data
     */
    protected function _form_filter(array &$data)
    {
        if (empty($data['code'])) {
            $data['code'] = CodeExtend::uniqidDate(16, 'T');
        }
    }

    /**
     * 表单结果处理
     * @param bool $result
     */
    protected function _form_result(bool $result)
    {
        if ($result) {
            $this->success('编辑模板成功', 'javascript:history.back()');
        }
    }

    /**
     * 修改模板状态
     * @auth true
     */
    public function state()
    {
        PluginWumaSourceTemplate::mSave();
    }

    /**
     * 删除溯源模板
     * @auth true
     * @return void
     * @throws \think\db\exception\DbException
     */
    public function remove()
    {
        $subsql = PluginWumaSourceTemplate::mk()->whereIn('id', str2arr(input('id', '')))->field('code')->buildSql();
        $batchs = PluginWumaSourceProduce::mk()->whereRaw("tcode in {$subsql}")->distinct()->column('tcode');
        if (count($batchs) > 0) $this->error('删除失败，以下模板已经使用！<br><b>' . join('</b><br><b>', $batchs) . '</b>');
        PluginWumaSourceTemplate::mDelete();
    }
}