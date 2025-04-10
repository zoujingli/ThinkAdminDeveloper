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

namespace plugin\wuma\controller\warehouse;

use plugin\wuma\model\PluginWumaWarehouseOrderData;
use plugin\wuma\model\PluginWumaWarehouseOrderDataMins;
use plugin\wuma\service\CodeService;
use think\admin\Controller;
use think\admin\helper\QueryHelper;

/**
 * 仓库标签流转历史
 * @class History
 * @package plugin\wuma\controller\warehouse
 */
class History extends Controller
{
    /**
     * 仓库库存历史
     * @menu true
     * @auth true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        PluginWumaWarehouseOrderDataMins::mQuery($this->get)->layTable(function () {
            $this->title = '仓库库存历史';
        }, function (QueryHelper $query) {
            // 操作单号搜索
            $db = PluginWumaWarehouseOrderData::mQuery()->like('code')->dateBetween('create_time')->db();
            if ($db->getOptions('where')) $query->whereRaw("ddid in {$db->field('id')->buildSql()}");

            // 防伪编码解析
            if (($this->get['encode'] ?? '') !== '') {
                $this->get['minAlias'] = CodeService::code2min($this->get['encode']);
            }
            // 数据查询应用
            $query->with(['main'])->equal('code#min,code#minAlias');
        });
    }

    /**
     * 数据列表处理
     * @param array $data
     * @return void
     */
    protected function _index_page_filter(array &$data)
    {
        $codes = array_unique(array_column($data, 'code'));
        $outers = PluginWumaWarehouseOrderDataMins::mk()->whereIn('code', $codes)->column('code');
        foreach ($data as &$vo) if (in_array($vo['code'], $outers)) $vo['status'] = 2;
    }
}