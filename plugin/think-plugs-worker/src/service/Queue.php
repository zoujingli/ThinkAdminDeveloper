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

namespace plugin\worker\service;

use think\admin\contract\QueueHandlerInterface;
use think\admin\contract\QueueRuntimeInterface;
use think\admin\Exception;
use think\App;

/**
 * 队列任务基类.
 * @class Queue
 */
abstract class Queue implements QueueHandlerInterface
{
    protected App $app;

    protected QueueRuntimeInterface $queue;

    protected ProcessService $process;

    public function __construct(App $app, ProcessService $process)
    {
        $this->app = $app;
        $this->process = $process;
    }

    public function initialize(QueueRuntimeInterface $queue): static
    {
        $this->queue = $queue;
        return $this;
    }

    public function handle(QueueRuntimeInterface $queue): mixed
    {
        return $this->initialize($queue)->execute($queue->getData());
    }

    abstract public function execute(array $data = []);

    /**
     * @throws Exception
     */
    protected function setQueueError(string $message): void
    {
        $this->queue->error($message);
    }

    /**
     * @throws Exception
     */
    protected function setQueueSuccess(string $message): void
    {
        $this->queue->success($message);
    }

    /**
     * @throws Exception
     */
    protected function setQueueMessage(int $total, int $count, string $message = '', int $backline = 0): static
    {
        $this->queue->message($total, $count, $message, $backline);
        return $this;
    }

    /**
     * @throws Exception
     */
    protected function setQueueProgress(?string $message = null, ?string $progress = null, int $backline = 0): static
    {
        $this->queue->progress(2, $message, $progress, $backline);
        return $this;
    }
}
