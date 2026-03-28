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

use plugin\system\builder\QueueBuilder;
use plugin\system\service\QueueService as QueuePageService;
use plugin\worker\model\SystemQueue;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\admin\service\QueueService as QueueRuntimeService;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\HttpResponseException;

/**
 * 系统任务管理.
 * @class Queue
 */
class Queue extends Controller
{
    /**
     * 系统任务管理.
     * @auth true
     * @menu true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        $context = QueuePageService::buildIndexContext();
        SystemQueue::mQuery()->layTable(function () use ($context) {
            $this->respondWithPageBuilder(QueueBuilder::buildIndexPage($context), $context);
        }, static function (QueryHelper $query) {
            QueuePageService::applyIndexQuery($query);
        });
    }

    /**
     * 重启系统任务
     * @auth true
     */
    public function redo()
    {
        try {
            $data = $this->_vali(['code.require' => lang('任务编号不能为空！')]);
            $queue = QueueRuntimeService::instance()->initialize($data['code'])->reset();
            $queue->progress(1, strval(lang('>>> 任务重置成功 <<<')), '0.00');
            $this->success(lang('任务重置成功！'), $queue->getCode());
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            trace_file($exception);
            $this->error($exception->getMessage());
        }
    }

    /**
     * 清理运行数据.
     * @auth true
     */
    public function clean()
    {
        $this->_queue('定时清理系统运行数据', 'xadmin:queue clean', 0, [], 3600);
    }

    /**
     * 删除系统任务
     * @auth true
     */
    public function remove()
    {
        SystemQueue::mDelete();
    }

    /**
     * 分页数据回调处理.
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    protected function _index_page_filter(array $data, array &$result)
    {
        QueuePageService::enrichPageResult($result);
    }
}
