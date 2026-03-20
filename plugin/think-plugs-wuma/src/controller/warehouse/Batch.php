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
use think\admin\extend\CodeToolkit;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\db\Query;
use think\exception\HttpResponseException;

/**
 * 仓库批次出库.
 * @class Batch
 */
class Batch extends Controller
{
    /**
     * 仓库批次出库.
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        PluginWumaSourceAssign::mQuery()->layTable(function () {
            $this->title = '仓库批次出库';
        }, function (QueryHelper $query) {
            $query->withoutField('items');

            $query->with(['coder', 'range' => function (Query $relation) {
                $relation->with(['bindProduce']);
            }]);
            $query->like('batch,cbatch');
            $query->dateBetween('create_time');

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
                $db = PluginWumaCodeRuleRange::mQuery($this->get);
                $db->valueRange("range_start:range_after#{$alias}");
                if (!empty($db->getOptions()['where'] ?? [])) {
                    $query->whereRaw('cbatch in ' . $db->db()->field('batch')->whereIn('code_type', str2arr($type))->buildSql());
                }
            }
        });
    }

    /**
     * 按批次分区出库.
     * @auth true
     */
    public function edit()
    {
        $this->title = '按批次分区出库';
        $query = PluginWumaSourceAssign::mQuery();
        $query->with([
            'coder' => function ($relation) {
                $relation->with(['rules']);
            }, 'range' => function ($relation) {
                $relation->with(['bindProduce']);
            },
        ]);
        $query->mForm('form');
    }

    /**
     * 表单数据处理.
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    protected function _form_filter(array &$data)
    {
        if ($this->request->isGet()) {
            if (empty($data)) {
                $this->error('出库批次不存在！');
            }
            PluginWumaCodeRule::applyRangeData($data['coder']);
            unset($data['coder']['rules']);
            $this->warehouses = PluginWumaWarehouse::lists([
                'status' => 1,
            ]);
            RelationService::withMergeRange($data['range']);
        } else {
            $input = $this->_vali([
                'batch.require' => '赋码批次不能为空！',
                'wcode.require' => '出货仓库不能为空！',
                'items.require' => '出货分区不能为空！',
                'import.require' => '自动入库不能为空！',
            ], $data);
            $input['items'] = json_decode($input['items'], true);
            if (empty($input['items'])) {
                $this->error('待出库分区不能为空！');
            }
            $items = [];
            foreach ($input['items'] as &$range) {
                foreach ($range['items'] as &$item) {
                    if ($item['lock'] === 1 && ($item['agent'] ?? 0) > 0) {
                        $mins = range($item['min'], $item['max']);
                        if (count($mins) !== count($unis = array_unique($mins))) {
                            $this->error('分区存在重叠！');
                        }
                        $item['lock'] = 2;
                        $item['code'] = CodeToolkit::uniqidDate(16, 'BK');
                        $items[] = [
                            'mins' => join(',', $unis),
                            'code' => $item['code'],
                            'agent' => $item['agent'],
                            'batch' => $item['batch'],
                            'ghash' => $item['ghash'],
                            'wcode' => $input['wcode'],
                        ];
                    }
                }
            }
            try {
                if (empty($items)) {
                    $this->error('没有需要出货的数据！');
                }
                foreach ($items as &$item) {
                    $item['code'] = CodeToolkit::uniqidDate(16, 'BK');
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
     * 表单处理结果处理.
     */
    protected function _form_result(bool $status)
    {
        if ($status) {
            $this->success('数据保存成功', 'javascript:history.back()');
        }
    }
}
