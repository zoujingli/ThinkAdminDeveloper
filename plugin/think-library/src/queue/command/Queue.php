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

namespace think\admin\queue\command;

use think\admin\contract\QueueHandlerInterface;
use think\admin\Command;
use think\admin\model\SystemQueue;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\db\exception\DbException;

/**
 * 异步任务管理指令.
 * @class Queue
 */
class Queue extends Command
{
    /**
     * 任务等待处理.
     * @var int
     */
    public const STATE_WAIT = 1;

    /**
     * 任务正在处理.
     * @var int
     */
    public const STATE_LOCK = 2;

    /**
     * 任务处理完成.
     * @var int
     */
    public const STATE_DONE = 3;

    /**
     * 任务处理失败.
     * @var int
     */
    public const STATE_ERROR = 4;

    /**
     * 当前任务编号.
     * @var string
     */
    protected $code;

    /**
     * 指令任务配置.
     */
    public function configure()
    {
        $this->setName('xadmin:queue');
        $this->addArgument('action', Argument::OPTIONAL, 'clean|dorun');
        $this->addArgument('code', Argument::OPTIONAL, 'Taskcode');
        $this->setDescription('Execute and clean queue tasks for ThinkAdmin');
    }

    /**
     * 任务执行入口.
     */
    protected function execute(Input $input, Output $output)
    {
        $action = trim((string)$input->getArgument('action'));
        if (method_exists($this, $method = "{$action}Action")) {
            return $this->{$method}();
        }

        $this->output->error('Wrong operation, allow clean|dorun');
        return 1;
    }

    /**
     * 清理所有任务
     * @throws \think\admin\Exception
     * @throws DbException
     */
    protected function cleanAction()
    {
        // 清理任务历史记录
        $days = intval(sysconf('base.queue_clean_days|raw') ?: 7);
        $clean = SystemQueue::mk()->where('exec_time', '<', time() - $days * 24 * 3600)->delete();
        // 标记超过 1 小时未完成的任务为失败状态，循环任务失败重置
        $map1 = [['loops_time', '>', 0], ['status', '=', static::STATE_ERROR]]; // 执行失败的循环任务
        $map2 = [['exec_time', '<', time() - 3600], ['status', '=', static::STATE_LOCK]]; // 执行超时的任务
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
     * 执行指定任务
     * @throws \think\admin\Exception
     */
    protected function doRunAction()
    {
        $this->code = trim($this->input->getArgument('code'));
        if (empty($this->code)) {
            $this->output->error('Task number needs to be specified for task execution');
        } else {
            try {
                set_time_limit(0) && PHP_SAPI !== 'cli' && ignore_user_abort(true);
                $this->queue->initialize($this->code);
                if ($this->queue->getRecord()->isEmpty() || intval($this->queue->getRecord()->getAttr('status')) !== static::STATE_WAIT) {
                    // 这里不做任何处理（该任务可能在其它地方已经在执行）
                    $this->output->warning("The or status of task {$this->code} is abnormal");
                } else {
                    // 锁定任务状态，防止任务再次被执行
                    SystemQueue::mk()->strict(false)->where(['code' => $this->code])->inc('attempts')->update([
                        'enter_time' => microtime(true), 'outer_time' => 0, 'exec_pid' => getmypid(), 'exec_desc' => '', 'status' => static::STATE_LOCK,
                    ]);
                    $this->queue->progress(2, '>>> 任务处理开始 <<<', '0');
                    // 执行任务内容
                    defined('WorkQueueCall') or define('WorkQueueCall', true);
                    defined('WorkQueueCode') or define('WorkQueueCode', $this->code);
                    if (class_exists($command = $this->queue->getRecord()->getAttr('command'))) {
                        // 自定义任务，统一走任务契约执行
                        $class = $this->app->make($command, [], true);
                        if ($class instanceof QueueHandlerInterface) {
                            $this->updateQueue(static::STATE_DONE, $class->handle($this->queue) ?: '');
                        } else {
                            throw new \think\admin\Exception("自定义 {$command} 未实现 think\\admin\\contract\\QueueHandlerInterface");
                        }
                    } else {
                        // 自定义指令，不支持返回消息（支持异常结束，异常码可选择 3|4 设置任务状态）
                        $attr = explode(' ', trim(preg_replace('|\s+|', ' ', $command)));
                        $this->updateQueue(static::STATE_DONE, $this->app->console->call(array_shift($attr), $attr)->fetch(), false);
                    }
                }
            } catch (\Error|\Exception|\Throwable $exception) {
                $isDone = intval($exception->getCode()) === static::STATE_DONE;
                $this->updateQueue($isDone ? static::STATE_DONE : static::STATE_ERROR, $exception->getMessage());
            }
        }
    }

    /**
     * 修改当前任务状态
     * @param int $status 任务状态
     * @param string $message 消息内容
     * @param bool $isSplit 是否分隔
     * @throws \think\admin\Exception
     */
    private function updateQueue(int $status, string $message, bool $isSplit = true)
    {
        // 更新当前任务
        $desc = $isSplit ? explode("\n", trim($message)) : [$message];
        SystemQueue::mk()->strict(false)->where(['code' => $this->code])->update([
            'status' => $status, 'outer_time' => microtime(true), 'exec_pid' => getmypid(), 'exec_desc' => $desc[0],
        ]);
        $this->process->message($message);
        // 任务进度标记
        if (!empty($desc[0])) {
            $this->queue->progress($status, ">>> {$desc[0]} <<<");
        }
        // 任务状态标记
        if ($status === static::STATE_DONE) {
            $this->queue->progress($status, '>>> 任务处理完成 <<<', '100.00');
        } elseif ($status === static::STATE_ERROR) {
            $this->queue->progress($status, '>>> 任务处理失败 <<<');
        }
        // 注册循环任务
        if (($time = intval($this->queue->getRecord()->getAttr('loops_time'))) > 0) {
            try {
                $this->queue->initialize($this->code)->reset($time);
            } catch (\Error|\Exception|\Throwable $exception) {
                $this->app->log->error("Queue {$this->queue->getRecord()->getAttr('code')} Loops Failed. {$exception->getMessage()}");
            }
        }
    }

}
