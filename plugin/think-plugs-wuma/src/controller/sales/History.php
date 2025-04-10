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

namespace plugin\wuma\controller\sales;

use plugin\wuma\model\PluginWumaWarehouseOrderDataMins;
use plugin\wuma\service\CodeService;
use think\admin\Controller;
use think\admin\Exception;
use think\admin\helper\QueryHelper;

/**
 * 标签流转历史
 * @class History
 * @package plugin\wuma\controller\sales
 */
class History extends Controller
{
    /**
     * 标签流转历史
     * @menu true
     * @auth true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $data = $this->_vali(['type.default' => '', 'code.default' => '']);
        PluginWumaWarehouseOrderDataMins::mQuery()->layTable(function () use ($data) {
            $this->items = [];
            $this->title = '标签流转历史';
            if (!empty($data['type']) && !empty($data['code'])) try {
                if ($data['type'] === 'tag') {
                    $data['type'] = 'min';
                    $data['code'] = CodeService::code2min($data['code']);
                }
                [$this->batch, $this->items] = CodeService::tomins($data['type'], $data['code']);
            } catch (Exception $exception) {
                $this->error($exception->getMessage());
            } catch (\Exception $exception) {
                trace_file($exception);
            }
        }, static function (QueryHelper $query) {
            $query->with(['agent', 'pdata']);
            $query->equal('code')->order('id asc');
        });
    }
}