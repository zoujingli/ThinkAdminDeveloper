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

namespace plugin\wuma\controller\sales;

use plugin\wuma\model\PluginWumaWarehouseOrderDataMins;
use plugin\wuma\service\CodeService;
use think\admin\Controller;
use think\admin\Exception;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 标签流转历史.
 * @class History
 */
class History extends Controller
{
    /**
     * 标签流转历史.
     * @menu true
     * @auth true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        $data = $this->_vali(['type.default' => '', 'code.default' => '']);
        PluginWumaWarehouseOrderDataMins::mQuery()->layTable(function () use ($data) {
            $this->items = [];
            $this->title = '标签流转历史';
            if (!empty($data['type']) && !empty($data['code'])) {
                try {
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
            }
        }, static function (QueryHelper $query) {
            $query->with(['agent', 'pdata']);
            $query->equal('code')->order('id asc');
        });
    }
}
