<?php

// +----------------------------------------------------------------------
// | Wuma Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
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

namespace plugin\wuma\controller\warehouse;

use plugin\wuma\model\PluginWumaCodeRule;
use plugin\wuma\model\PluginWumaCodeRuleRange;
use plugin\wuma\model\PluginWumaSourceAssign;
use plugin\wuma\model\PluginWumaWarehouse;
use plugin\wuma\service\CodeService;
use plugin\wuma\service\RelationService;
use plugin\wuma\service\WhExportService;
use think\admin\Controller;
use think\admin\Exception;
use think\admin\extend\CodeExtend;
use think\admin\helper\QueryHelper;
use think\db\Query;
use think\exception\HttpResponseException;

/**
 * 仓库批次出库
 * @class Batch
 * @package plugin\wuma\controller\warehouse
 */
class Batch extends Controller
{
    /**
     * 仓库批次出库
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        PluginWumaSourceAssign::mQuery()->layTable(function () {
            $this->title = '仓库批次出库';
        }, static function (QueryHelper $query) {
            $query->withoutField('items')->where(['deleted' => 0]);

            $query->with(['coder', 'range' => function (Query $relation) {
                $relation->with(['bindProduce']);
            }])->like('batch,cbatch')->dateBetween('create_time');

            // 物码数值批次筛选
            if (isset($this->get['encode']) and $this->get['encode'] !== '') {
                if (is_numeric($this->get['encode'])) {
                    $this->get['numValue'] = CodeService::num2min($this->get['encode']) ?: 0;
                } else {
                    $this->get['encValue'] = CodeService::enc2min($this->get['encode']) ?: 0;
                }
            }
            // 批量创建筛选规则
            foreach (['minValue' => 'min', 'encValue' => 'min', 'numValue' => 'min'] as $alias => $type) {
                $db = PluginWumaCodeRuleRange::mQuery($this->get)->valueRange("range_start:range_after#{$alias}")->field('batch')->db();
                if ($db->getOptions('where')) $query->whereRaw('cbatch in ' . $db->whereIn('code_type', str2arr($type))->buildSql());
            }
        });
    }

    /**
     * 按批次分区出库
     * @auth true
     * @return void
     * @throws \think\admin\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function edit()
    {
        $this->title = '按批次分区出库';
        $this->_form(PluginWumaSourceAssign::mk()->with([
            'coder'    => function ($relation) {
                $relation->with(['rules']);
            }, 'range' => function ($relation) {
                $relation->with(['bindProduce']);
            },
        ]), 'form');
    }

    /**
     * 表单数据处理
     * @param array $data
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function _form_filter(array &$data)
    {
        if ($this->request->isGet()) {
            if (empty($data)) $this->error('出库批次不存在！');
            PluginWumaCodeRule::applyRangeData($data['coder']);
            unset($data['coder']['rules']);
            $this->warehouses = PluginWumaWarehouse::lists([
                'status' => 1, 'deleted' => 0,
            ]);
            RelationService::withMergeRange($data['range']);
        } else {
            $input = $this->_vali([
                'batch.require'  => '赋码批次不能为空！',
                'wcode.require'  => '出货仓库不能为空！',
                'items.require'  => '出货分区不能为空！',
                'import.require' => '自动入库不能为空！',
            ], $data);
            $input['items'] = json_decode($input['items'], true);
            if (empty($input['items'])) $this->error('待出库分区不能为空！');
            $items = [];
            foreach ($input['items'] as &$range) foreach ($range['items'] as &$item) {
                if ($item['lock'] === 1 && ($item['agent'] ?? 0) > 0) {
                    $mins = range($item['min'], $item['max']);
                    if (count($mins) !== count($unis = array_unique($mins))) {
                        $this->error('分区存在重叠！');
                    }
                    $item['lock'] = 2;
                    $item['code'] = CodeExtend::uniqidDate(16, 'BK');
                    $items[] = [
                        'mins'  => join(',', $unis),
                        'code'  => $item['code'],
                        'agent' => $item['agent'],
                        'batch' => $item['batch'],
                        'ghash' => $item['ghash'],
                        'wcode' => $input['wcode'],
                    ];
                }
            }
            try {
                if (empty($items)) {
                    $this->error('没有需要出货的数据！');
                }
                foreach ($items as &$item) {
                    $item['code'] = CodeExtend::uniqidDate(16, 'BK');
                    WhExportService::batch($item, !empty($input['import']));
                }
                PluginWumaSourceAssign::mk()->where(['batch' => $input['batch']])->update([
                    'outer_items' => json_encode($input['items'], JSON_UNESCAPED_UNICODE),
                ]);
                $this->success('产品出库成功！');
            } catch (HttpResponseException $exception) {
                throw $exception;
            } catch (Exception $exception) {
                $data = is_array($exception->getData()) ? $exception->getData() : [];
                $this->error($exception->getMessage() . '<br>' . join(',', $data), $exception->getData());
            } catch (\Exception $exception) {
                trace_file($exception);
                $this->error($exception->getMessage());
            }
        }
    }

    /**
     * 表单处理结果处理
     * @param bool $status
     */
    protected function _form_result(bool $status)
    {
        if ($status) $this->success('数据保存成功', 'javascript:history.back()');
    }
}