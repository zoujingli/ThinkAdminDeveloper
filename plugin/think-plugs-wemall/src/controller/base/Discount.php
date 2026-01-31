<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | Payment Plugin for ThinkAdmin
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

namespace plugin\wemall\controller\base;

use plugin\wemall\model\PluginWemallConfigDiscount;
use plugin\wemall\model\PluginWemallConfigLevel;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 折扣方案管理.
 * @class Discount
 */
class Discount extends Controller
{
    /**
     * 折扣方案管理.
     * @auth true
     * @menu true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        $this->type = $this->get['type'] ?? 'index';
        PluginWemallConfigDiscount::mQuery()->layTable(function () {
            $this->title = '折扣方案管理';
        }, function (QueryHelper $query) {
            $query->where(['status' => intval($this->type === 'index'), 'deleted' => 0]);
        });
    }

    /**
     * 添加折扣方案.
     * @auth true
     */
    public function add()
    {
        PluginWemallConfigDiscount::mForm('form');
    }

    /**
     * 编辑折扣方案.
     * @auth true
     */
    public function edit()
    {
        PluginWemallConfigDiscount::mForm('form');
    }

    /**
     * 修改折扣方案状态
     * @auth true
     */
    public function state()
    {
        PluginWemallConfigDiscount::mSave();
    }

    /**
     * 删除折扣方案配置.
     * @auth true
     */
    public function remove()
    {
        PluginWemallConfigDiscount::mDelete();
    }

    /**
     * 表单数据处理.
     */
    protected function _form_filter(array &$vo)
    {
        if ($this->request->isPost()) {
            $rule = [];
            foreach ($vo as $k => $v) {
                if (stripos($k, '_level_') !== false) {
                    [, $level] = explode('_level_', $k);
                    $rule[] = ['level' => $level, 'discount' => $v];
                }
            }
            $vo['items'] = json_encode($rule, JSON_UNESCAPED_UNICODE);
        } else {
            $this->levels = PluginWemallConfigLevel::items();
            if (empty($this->levels)) {
                $this->error('未配置会员等级！');
            }
            foreach ($vo['items'] ?? [] as $item) {
                $vo["_level_{$item['level']}"] = $item['discount'];
            }
        }
    }
}
