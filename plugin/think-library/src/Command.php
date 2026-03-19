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

namespace think\admin;

use think\admin\contract\QueueManagerInterface;
use think\admin\service\QueueService;
use think\console\Input;
use think\console\Output;

/**
 * Shared command base class.
 */
abstract class Command extends \think\console\Command
{
    protected QueueManagerInterface $queue;

    public function setQueueMessage(int $total, int $count, string $message = '', int $backline = 0): static
    {
        $this->queue->message($total, $count, $message, $backline);
        return $this;
    }

    protected function initialize(Input $input, Output $output): static
    {
        $this->queue = QueueService::instance();
        if (($code = QueueService::currentCode()) !== '' && $this->queue->getCode() !== $code) {
            $this->queue->initialize($code);
        }

        return $this;
    }

    protected function setQueueError(string $message): void
    {
        if (QueueService::inContext()) {
            $this->queue->error($message);
        } else {
            $this->writeConsoleMessage($message);
            exit(0);
        }
    }

    protected function setQueueSuccess(string $message): void
    {
        if (QueueService::inContext()) {
            $this->queue->success($message);
        } else {
            $this->writeConsoleMessage($message);
            exit(0);
        }
    }

    protected function setQueueProgress(?string $message = null, ?string $progress = null, int $backline = 0): static
    {
        if (QueueService::inContext()) {
            $this->queue->progress(QueueService::STATE_LOCK, $message, $progress, $backline);
        } elseif (is_string($message)) {
            $this->writeConsoleMessage($message, $backline);
        }

        return $this;
    }

    protected function writeConsoleMessage(string $message, int $backline = 0): void
    {
        while ($backline-- > 0) {
            $message = "\033[1A\r\033[K{$message}";
        }

        $this->output->write($message . PHP_EOL);
    }
}
