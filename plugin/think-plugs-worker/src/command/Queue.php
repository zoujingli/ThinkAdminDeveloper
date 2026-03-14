<?php

declare(strict_types=1);

namespace plugin\worker\command;

use plugin\worker\model\SystemQueue;
use plugin\worker\service\QueueExecutor;
use plugin\worker\service\QueueService;
use think\admin\Command;
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

    /**
     * 当前任务编号.
     */
    protected string $code = '';

    public function configure(): void
    {
        $this->setName('xadmin:queue');
        $this->addArgument('action', Argument::OPTIONAL, 'clean|dorun');
        $this->addArgument('code', Argument::OPTIONAL, 'Taskcode');
        $this->setDescription('Execute and clean queue tasks for ThinkPlugsWorker');
    }

    protected function execute(Input $input, Output $output): int
    {
        $action = trim((string)$input->getArgument('action'));
        if (method_exists($this, $method = "{$action}Action")) {
            return $this->{$method}();
        }

        $this->output->error('Wrong operation, allow clean|dorun');
        return 1;
    }

    /**
     * 清理所有任务.
     *
     * @throws \think\admin\Exception
     * @throws DbException
     */
    protected function cleanAction(): void
    {
        $days = function_exists('sysconf') ? intval(sysconf('base.queue_clean_days|raw') ?: 7) : 7;
        $clean = SystemQueue::mk()->where('exec_time', '<', time() - $days * 24 * 3600)->delete();
        $map1 = [['loops_time', '>', 0], ['status', '=', static::STATE_ERROR]];
        $map2 = [['exec_time', '<', time() - 3600], ['status', '=', static::STATE_LOCK]];
        [$timeout, $loops, $total] = [0, 0, SystemQueue::mk()->whereOr([$map1, $map2])->count()];
        foreach (SystemQueue::mk()->whereOr([$map1, $map2])->cursor() as $queue) {
            $queue['loops_time'] > 0 ? $loops++ : $timeout++;
            if ($queue['loops_time'] > 0) {
                $this->queue->message($total, $timeout + $loops, "正在重置任务 {$queue['code']} 为运行");
                [$status, $message] = [static::STATE_WAIT, $queue['status'] === static::STATE_ERROR ? '任务执行失败，已自动重置任务！' : '任务执行超时，已自动重置任务！'];
            } else {
                $this->queue->message($total, $timeout + $loops, "正在标记任务 {$queue['code']} 为超时");
                [$status, $message] = [static::STATE_ERROR, '任务执行超时，已自动标识为失败！'];
            }
            $queue->save(['status' => $status, 'exec_desc' => $message]);
        }
        $this->setQueueSuccess("清理 {$clean} 条历史任务，关闭 {$timeout} 条超时任务，重置 {$loops} 条循环任务");
    }

    /**
     * 执行指定任务.
     *
     * @throws \think\admin\Exception
     */
    protected function doRunAction(): void
    {
        $this->code = trim((string)$this->input->getArgument('code'));
        if ($this->code === '') {
            $this->output->error('Task number needs to be specified for task execution');
            return;
        }

        $result = QueueExecutor::instance()->run($this->code);
        if (!in_array($result['status'], [static::STATE_DONE, static::STATE_ERROR], true)) {
            $this->output->warning($result['message']);
        }
    }
}
