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

namespace plugin\wuma\controller\source;

use plugin\wuma\model\PluginWumaCodeRule;
use plugin\wuma\model\PluginWumaCodeRuleRange;
use plugin\wuma\model\PluginWumaSourceAssign;
use plugin\wuma\model\PluginWumaSourceAssignItem;
use plugin\wuma\model\PluginWumaSourceProduce;
use plugin\wuma\service\CodeService;
use plugin\wuma\service\RelationService;
use think\admin\Controller;
use think\admin\extend\CodeExtend;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\db\Query;
use think\exception\HttpResponseException;

/**
 * 赋码批次管理.
 * @class Assign
 */
class Assign extends Controller
{
    /**
     * 赋码批次管理.
     * @menu true
     * @auth true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        PluginWumaSourceAssign::mQuery()->layTable(function () {
            $this->title = '赋码批次管理';
        }, function (QueryHelper $query) {
            $query->withoutField('items')->where(['deleted' => 0]);
            $query->like('batch,cbatch')->dateBetween('create_time');
            $query->withCount(['range' => 'total'])->with([
                'coder' => static function (Query $query) {
                    $query->field('type,batch');
                },
                'range' => static function (Query $query) {
                    $query->with(['bindProduce'])->limit(0, 1);
                },
            ]);
            // 物码数值批次筛选
            if (!empty($this->get['encode'])) {
                if (is_numeric($this->get['encode'])) {
                    $this->get['numValue'] = CodeService::num2min($this->get['encode']) ?: 0;
                } else {
                    $this->get['encValue'] = CodeService::enc2min($this->get['encode']) ?: 0;
                }
            }
            // 批量创建筛选规则
            foreach (['min#minValue', 'min#encValue', 'min#numValue'] as $rule) {
                [$type, $alias] = explode('#', $rule);
                $db = PluginWumaCodeRuleRange::mQuery($this->get)->valueRange("range_start:range_after#{$alias}")->field('batch')->db();
                if ($db->getOptions('where')) {
                    $query->whereRaw("cbatch in {$db->whereIn('code_type', str2arr($type))->buildSql()}");
                }
            }
        });
    }

    /**
     * 添加赋码批次
     * @auth true
     */
    public function add()
    {
        $this->title = '添加赋码批次';
        PluginWumaSourceAssign::mForm('form');
    }

    /**
     * 编辑赋码批次
     * @auth true
     */
    public function edit()
    {
        $this->title = '编辑赋码批次';
        PluginWumaSourceAssign::mForm('form');
    }

    /**
     * 切换赋码模式.
     * @auth true
     * @throws \think\admin\Exception
     */
    public function mode()
    {
        $data = $this->_vali(['batch.require' => '赋码批次不能为空！']);
        $assign = PluginWumaSourceAssign::mk()->where($data)->findOrEmpty();
        if ($assign->isEmpty()) {
            $this->error('无效的批次码！');
        }
        if ($assign->getAttr('type') == 0) {
            $assign->save(['type' => 1]);
            RelationService::resetAssignLock($data['batch'], true);
        } else {
            $assign->save(['type' => 0]);
            RelationService::resetAssignLock($data['batch']);
        }
        $this->success('切换模式成功！');
    }

    /**
     * 重置分区锁定.
     * @auth true
     */
    public function unlock()
    {
        try {
            $data = $this->_vali(['batch.require' => '赋码批次不能为空！']);
            RelationService::resetAssignLock($data['batch']);
            $this->success('刷新区间锁定成功！');
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * 表单数据处理.
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    protected function _form_filter(array &$data)
    {
        if (empty($data['batch'])) {
            $data['batch'] = CodeExtend::uniqidDate(16, 'F');
        }
        if ($this->request->isGet()) {
            // 生产批次数据
            $this->produces = PluginWumaSourceProduce::lists(['status' => 1, 'deleted' => 0]);
            // 物码批次数据
            $this->coders = PluginWumaCodeRule::lists(static function (Query $query) use ($data) {
                $subsql1 = empty($data['cbatch']) ? '' : "batch='{$data['cbatch']}' OR";
                $subsql2 = PluginWumaSourceAssign::mk()->where(['status' => 1, 'deleted' => 0])->field('cbatch')->buildSql();
                $query->where(['deleted' => 0])->whereRaw("number>0 and ({$subsql1} batch not in {$subsql2})");
            });
            if (empty($this->coders)) {
                $this->error('物码批次不能为空！');
            }
            if (empty($this->produces)) {
                $this->error('生产批次不能为空！');
            }
            // 关联赋码区间
            $data['items'] = PluginWumaSourceAssignItem::mk()->field([
                'lock', 'real', 'pbatch' => 'batch', 'range_start' => 'min', 'range_after' => 'max',
            ])->where(['batch' => $data['batch']])->select()->toJson();
        } else {
            $items = [];
            foreach (json_decode($data['items'], true) as $item) {
                $items[] = [
                    'real' => $item['real'] ?? 0,
                    'lock' => $item['lock'] ?? 0,
                    'batch' => $data['batch'],
                    'pbatch' => $item['batch'],
                    'cbatch' => $data['cbatch'],
                    'range_start' => $item['min'],
                    'range_after' => $item['max'],
                    'create_time' => date('Y-m-d H:i:s'),
                    'update_time' => date('Y-m-d H:i:s'),
                ];
            }
            $map = ['batch' => $data['batch']];
            PluginWumaSourceAssignItem::mk()->where($map)->delete();
            PluginWumaSourceAssignItem::mk()->insertAll($items);
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
