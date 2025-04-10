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

namespace plugin\wuma\controller;

use plugin\wuma\model\PluginWumaCodeRule;
use plugin\wuma\model\PluginWumaCodeRuleRange;
use plugin\wuma\service\CodeService;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\exception\HttpResponseException;

/**
 * 物码标签管理
 * @class Code
 * @package plugin\wuma\controller
 */
class Code extends Controller
{
    /**
     * 仓库物码管理
     * @auth true
     * @menu true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->type = $this->get['type'] ?? 'index';
        PluginWumaCodeRule::mQuery()->layTable(function () {
            $this->title = '仓库物码管理';
        }, function (QueryHelper $query) {
            // 物码数据筛选
            $query->where(['deleted' => 0, 'status' => intval($this->type === 'index')]);
            $query->with(['rules'])->equal('type#mtype')->like('batch')->dateBetween('create_time');
            // 物码数值批次筛选
            if (isset($this->get['encode']) && $this->get['encode'] !== '') {
                if (is_numeric($this->get['encode'])) {
                    $this->get['numValue'] = CodeService::num2min($this->get['encode']) ?: 0;
                } else {
                    $this->get['encValue'] = CodeService::enc2min($this->get['encode']) ?: 0;
                }
            }
            // 批量创建筛选规则
            foreach (['minValue#min', 'boxValue#max,mid', 'encValue#min', 'numValue#min'] as $rule) {
                [$alias, $types] = explode('#', $rule);
                $db = PluginWumaCodeRuleRange::mQuery($this->get)->valueRange("range_start:range_after#{$alias}")->field('batch')->db();
                if ($db->getOptions('where')) $query->whereRaw("batch in {$db->whereIn('code_type', str2arr($types))->buildSql()}");
            }
        });
    }

    /**
     * 数据列表处理
     * @param array $data
     * @return void
     */
    protected function _index_page_filter(array &$data)
    {
        foreach ($data as &$vo) PluginWumaCodeRule::applyRangeData($vo);
    }

    /**
     * 下载物码文件
     * @auth true
     */
    public function download()
    {
        $data = $this->_vali(['batch.require' => '物码批次不能为空！']);
        if (file_exists($file = CodeService::withFile($data['batch']))) {
            download($file, basename($file), false, 1)->send();
        } else {
            [$state, $info, $file] = CodeService::create($data['batch']);
            empty($state) ? $this->error($info) : download($file, basename($file), false, 1)->send();
        }
    }

    /**
     * 修改导出模板
     * @auth true
     */
    public function template()
    {
        if ($this->request->isGet()) {
            $this->fields = CodeService::FILEDS;
            PluginWumaCodeRule::mForm('template');
        } else {
            $data = $this->_vali([
                'batch.require'    => "批次号不能为空！",
                'remark.default'   => '',
                'template.default' => '',
            ]);
            PluginWumaCodeRule::mk()->where(['batch' => $data['batch']])->update([
                'remark' => $data['remark'], 'template' => $data['template']
            ]);
            $this->success('模板修改成功！');
        }
    }

    /**
     * 创建物码批次
     * @auth true
     */
    public function add()
    {
        if ($this->request->isGet()) {
            PluginWumaCodeRule::mForm('form');
        } else {
            $tpl = PluginWumaCodeRule::mk()->order('id desc')->value('template');
            $data = $this->_vali([
                'type.default'       => 1,
                'sns_length.default' => 0,
                'max_length.default' => 0,
                'mid_length.default' => 0,
                'min_length.default' => 0,
                'hex_length.default' => 0,
                'ver_length.default' => 0,
                'max_mid.default'    => 0,
                'mid_min.default'    => 0,
                'max_number.default' => 0,
                'mid_number.default' => 0,
                'remark.default'     => '',
                'number.default'     => 0,
                'template.default'   => $tpl,
            ]);
            // 创建物码规则并返回结果
            throw new HttpResponseException(json(array_merge(CodeService::add($data), ['data' => []])));
        }
    }

    /**
     * 创建生码任务
     * @auth true
     */
    public function queue()
    {
        $data = $this->_vali(['batch.require' => '批次号不能为空！']);
        $this->_queue("创建物码 {$data['batch']} 压缩文件", "xdata:wuma:create {$data['batch']}");
    }

    /**
     * 修改物码状态
     * @auth true
     */
    public function state()
    {
        PluginWumaCodeRule::mSave($this->_vali([
            'status.in:0,1'  => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }
}