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

namespace plugin\worker\command;

use plugin\system\service\ConfigService as SystemConfigService;
use plugin\worker\model\SystemQueue;
use plugin\worker\service\QueueExecutor;
use plugin\worker\service\QueueService;
use think\admin\Command;
use think\admin\Exception;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\db\exception\DbException;

/**
 * Queue maintenance command owned by ThinkPlugsWorker.
 */
class Queue extends Command
{
    public const STATE_WAIT = QueueService::STATE_WAIT;

    public const STATE_LOCK = QueueService::STATE_LOCK;

    public const STATE_DONE = QueueService::STATE_DONE;

    public const STATE_ERROR = QueueService::STATE_ERROR;

    protected string $code = '';

    public function configure(): void
    {
        $this->setName('xadmin:queue');
        $this->addArgument('action', Argument::OPTIONAL, 'clean|dorun');
        $this->addArgument('code', Argument::OPTIONAL, '任务编号');
        $this->setDescription('执行或清理系统队列任务');
    }

    protected function execute(Input $input, Output $output): int
    {
        $action = trim((string)$input->getArgument('action'));
        if (method_exists($this, $method = "{$action}Action")) {
            return $this->{$method}();
        }

        $this->output->error('错误的操作类型，仅支持 clean|dorun');
        return 1;
    }

    /**
     * @throws Exception
     * @throws DbException
     */
    protected function cleanAction(): void
    {
        $retainDays = intval(SystemConfigService::getRuntimeConfig()['queue_retain_days'] ?? $this->queueConfig('retain_days', 7));
        $retainDays = max(1, $retainDays);
        $lockTimeout = max(60, intval($this->queueConfig('lock_timeout', 3600)));

        $historyCutoff = microtime(true) - $retainDays * 86400;
        $clean = SystemQueue::mk()->where([
            ['loops_time', '=', 0],
            ['status', 'in', [static::STATE_DONE, static::STATE_ERROR]],
            ['outer_time', '>', 0],
            ['outer_time', '<', $historyCutoff],
        ])->delete();

        $loopMap = [['loops_time', '>', 0], ['status', '=', static::STATE_ERROR]];
        $lockMap = [['status', '=', static::STATE_LOCK], ['enter_time', '>', 0], ['enter_time', '<', microtime(true) - $lockTimeout]];
        $items = SystemQueue::mk()->whereOr([$loopMap, $lockMap])->order('id asc')->select();

        [$timeout, $loops, $total] = [0, 0, count($items)];
        $runtime = QueueService::instance([], true);

        foreach ($items as $queue) {
            $code = strval($queue['code']);
            $count = $timeout + $loops + 1;

            if (intval($queue['loops_time']) > 0) {
                ++$loops;
                $this->queue->message($total, $count, "正在重置任务 {$code} 为下次执行");
                $runtime->initialize($code)->reset(intval($queue['loops_time']));
                continue;
            }

            ++$timeout;
            $this->queue->message($total, $count, "正在标记任务 {$code} 为超时");
            $message = '任务执行超时，已自动标记为失败！';
            $queue->save([
                'status' => static::STATE_ERROR,
                'exec_pid' => 0,
                'outer_time' => microtime(true),
                'exec_desc' => $message,
            ]);
            $runtime->initialize($code)->progress(static::STATE_ERROR, $message, '0.00');
        }

        $this->setQueueSuccess("清理 {$clean} 条历史任务，关闭 {$timeout} 条超时任务，重置 {$loops} 条循环任务");
    }

    /**
     * @throws Exception
     */
    protected function doRunAction(): void
    {
        $this->code = trim((string)$this->input->getArgument('code'));
        if ($this->code === '') {
            $this->output->error('执行任务需要指定任务编号');
            return;
        }

        $result = QueueExecutor::instance()->run($this->code);
        if (!in_array($result['status'], [static::STATE_DONE, static::STATE_ERROR], true)) {
            $this->output->warning($result['message']);
        }
    }

    private function queueConfig(string $name, mixed $default = null): mixed
    {
        return $this->app->config->get("worker.services.queue.queue.{$name}", $default);
    }
}
