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

namespace think\admin;

use think\admin\contract\QueueManagerInterface;
use think\admin\service\ProcessService;
use think\admin\service\QueueService;
use think\console\Input;
use think\console\Output;

/**
 * 自定义指令基类.
 * @class Command
 */
abstract class Command extends \think\console\Command
{
    /**
     * 任务控制服务
     */
    protected QueueManagerInterface $queue;

    /**
     * 进程控制服务
     */
    protected ProcessService $process;

    /**
     * 更新任务进度.
     * @param int $total 记录总和
     * @param int $count 当前记录
     * @param string $message 文字描述
     * @param int $backline 回退行数
     */
    public function setQueueMessage(int $total, int $count, string $message = '', int $backline = 0): static
    {
        $this->queue->message($total, $count, $message, $backline);
        return $this;
    }

    /**
     * 初始化指令变量.
     * @return $this
     */
    protected function initialize(Input $input, Output $output): static
    {
        $this->queue = QueueService::instance();
        $this->process = ProcessService::instance();
        if (($code = QueueService::currentCode()) !== '' && $this->queue->getCode() !== $code) {
            $this->queue->initialize($code);
        }
        return $this;
    }

    /**
     * 设置失败消息并结束进程.
     * @param string $message 消息内容
     */
    protected function setQueueError(string $message): void
    {
        if (QueueService::inContext()) {
            $this->queue->error($message);
        } else {
            $this->process->message($message);
            exit(0);
        }
    }

    /**
     * 设置成功消息并结束进程.
     * @param string $message 消息内容
     */
    protected function setQueueSuccess(string $message): void
    {
        if (QueueService::inContext()) {
            $this->queue->success($message);
        } else {
            $this->process->message($message);
            exit(0);
        }
    }

    /**
     * 设置进度消息并继续执行.
     * @param null|string $message 进度消息
     * @param null|string $progress 进度数值
     * @param int $backline 回退行数
     */
    protected function setQueueProgress(?string $message = null, ?string $progress = null, int $backline = 0): static
    {
        if (QueueService::inContext()) {
            $this->queue->progress(QueueService::STATE_LOCK, $message, $progress, $backline);
        } elseif (is_string($message)) {
            $this->process->message($message, $backline);
        }
        return $this;
    }
}
