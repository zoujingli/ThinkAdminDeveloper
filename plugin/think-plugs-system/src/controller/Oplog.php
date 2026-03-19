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

namespace plugin\system\controller;

use plugin\system\model\SystemOplog;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\HttpResponseException;

/**
 * 系统日志管理.
 * @class Oplog
 */
class Oplog extends Controller
{
    /**
     * 系统日志管理.
     * @auth true
     * @menu true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        SystemOplog::mQuery()->layTable(function () {
            $this->title = '系统日志管理';
            $columns = SystemOplog::mk()->column('action,username', 'id');
            $this->users = array_unique(array_column($columns, 'username'));
            $this->actions = array_unique(array_column($columns, 'action'));
        }, static function (QueryHelper $query) {
            $query->dateBetween('create_time')->equal('username,action')->like('content,geoip,node');
        });
    }

    /**
     * 清理系统日志.
     * @auth true
     */
    public function clear()
    {
        try {
            SystemOplog::mQuery()->empty();
            sysoplog('系统运维管理', '成功清理所有日志');
            $this->success('日志清理成功！');
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            trace_file($exception);
            $this->error(lang('日志清理失败，%s', [$exception->getMessage()]));
        }
    }

    /**
     * 删除系统日志.
     * @auth true
     */
    public function remove()
    {
        SystemOplog::mDelete();
    }

    /**
     * 列表数据处理.
     * @throws \Exception
     */
    protected function _index_page_filter(array &$data)
    {
        $region = new \Ip2Region();
        foreach ($data as &$vo) {
            try {
                $vo['geoisp'] = $region->simple($vo['geoip']);
            } catch (\Exception $exception) {
                $vo['geoip'] = $exception->getMessage();
            }
        }
    }
}
