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

namespace plugin\wuma\controller\warehouse;

use plugin\wuma\model\PluginWumaWarehouseOrderData;
use plugin\wuma\model\PluginWumaWarehouseOrderDataMins;
use plugin\wuma\service\CodeService;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 仓库标签流转历史.
 * @class History
 */
class History extends Controller
{
    /**
     * 仓库库存历史.
     * @menu true
     * @auth true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        PluginWumaWarehouseOrderDataMins::mQuery($this->get)->layTable(function () {
            $this->title = '仓库库存历史';
        }, function (QueryHelper $query) {
            // 操作单号搜索
            $db = PluginWumaWarehouseOrderData::mQuery()->like('code')->dateBetween('create_time')->db();
            if ($db->getOptions('where')) {
                $query->whereRaw("ddid in {$db->field('id')->buildSql()}");
            }

            // 防伪编码解析
            if (($this->get['encode'] ?? '') !== '') {
                $this->get['minAlias'] = CodeService::code2min($this->get['encode']);
            }
            // 数据查询应用
            $query->with(['main'])->equal('code#min,code#minAlias');
        });
    }

    /**
     * 数据列表处理.
     */
    protected function _index_page_filter(array &$data)
    {
        $codes = array_unique(array_column($data, 'code'));
        $outers = PluginWumaWarehouseOrderDataMins::mk()->whereIn('code', $codes)->column('code');
        foreach ($data as &$vo) {
            if (in_array($vo['code'], $outers)) {
                $vo['status'] = 2;
            }
        }
    }
}
