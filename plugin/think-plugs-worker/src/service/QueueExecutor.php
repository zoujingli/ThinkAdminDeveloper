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

namespace plugin\worker\service;

use plugin\worker\model\SystemQueue;
use think\admin\contract\QueueHandlerInterface;
use think\admin\Exception;
use think\admin\runtime\RequestContext;
use think\admin\service\ProcessService;
use think\admin\service\Service;

/**
 * Shared queue executor for CLI and Worker runtimes.
 */
class QueueExecutor extends Service
{
    /**
     * Run a queue record in the current process.
     *
     * @return array{code:string,status:int,message:string}
     */
    public function run(string $code, bool $locked = false): array
    {
        $queue = QueueService::instance([], true);

        try {
            set_time_limit(0) && PHP_SAPI !== 'cli' && ignore_user_abort(true);
            $queue->initialize($code);

            if (!$locked) {
                if ($queue->getRecord()->isEmpty() || intval($queue->getRecord()->getAttr('status')) !== QueueService::STATE_WAIT) {
                    return ['code' => $code, 'status' => intval($queue->getRecord()->getAttr('status') ?: 0), 'message' => "Queue {$code} is not in a runnable state"];
                }

                if (!$this->lock($code)) {
                    return ['code' => $code, 'status' => QueueService::STATE_LOCK, 'message' => "Queue {$code} is already locked"];
                }

                $queue->initialize($code);
            }

            QueueService::enterContext($code);
            $queue->progress(QueueService::STATE_LOCK, '>>> 任务处理开始 <<<', '0.00');

            $command = (string)$queue->getRecord()->getAttr('command');
            if (class_exists($command)) {
                $class = $this->app->make($command, [], true);
                if (!$class instanceof QueueHandlerInterface) {
                    throw new Exception("Custom queue handler {$command} must implement think\\admin\\contract\\QueueHandlerInterface");
                }

                return $this->finish($queue, QueueService::STATE_DONE, strval($class->handle($queue) ?: ''));
            }

            $attr = explode(' ', trim(preg_replace('|\s+|', ' ', $command)));
            $output = $this->app->console->call(array_shift($attr), $attr)->fetch();
            return $this->finish($queue, QueueService::STATE_DONE, $output, false);
        } catch (\Throwable $exception) {
            if ($queue->getCode() === '' || !isset($queue->record) || $queue->getRecord()->isEmpty()) {
                return ['code' => $code, 'status' => QueueService::STATE_ERROR, 'message' => $exception->getMessage()];
            }

            $status = intval($exception->getCode()) === QueueService::STATE_DONE ? QueueService::STATE_DONE : QueueService::STATE_ERROR;
            return $this->finish($queue, $status, $exception->getMessage());
        } finally {
            QueueService::leaveContext();
            RequestContext::clear();
            function_exists('sysvar') && sysvar('', '');
        }
    }

    /**
     * Lock a queue record for execution.
     */
    protected function lock(string $code): bool
    {
        return SystemQueue::mk()->strict(false)->where([
            ['code', '=', $code],
            ['status', '=', QueueService::STATE_WAIT],
        ])->inc('attempts')->update([
            'enter_time' => microtime(true),
            'outer_time' => 0,
            'exec_pid' => getmypid(),
            'exec_desc' => '',
            'status' => QueueService::STATE_LOCK,
        ]) > 0;
    }

    /**
     * Persist queue result and progress messages.
     *
     * @return array{code:string,status:int,message:string}
     */
    protected function finish(QueueService $queue, int $status, string $message, bool $split = true): array
    {
        $code = $queue->getCode();
        $message = trim($message);
        $desc = $split ? explode("\n", $message) : [$message];
        $first = trim((string)($desc[0] ?? ''));

        SystemQueue::mk()->strict(false)->where(['code' => $code])->update([
            'status' => $status,
            'outer_time' => microtime(true),
            'exec_pid' => getmypid(),
            'exec_desc' => $first,
        ]);

        $message !== '' && ProcessService::message($message);

        if ($first !== '') {
            $queue->progress($status, ">>> {$first} <<<");
        }
        if ($status === QueueService::STATE_DONE) {
            $queue->progress($status, '>>> 任务处理完成 <<<', '100.00');
        } elseif ($status === QueueService::STATE_ERROR) {
            $queue->progress($status, '>>> 任务处理失败 <<<');
        }

        if (($time = intval($queue->getRecord()->getAttr('loops_time'))) > 0) {
            try {
                $queue->initialize($code)->reset($time);
            } catch (\Throwable $exception) {
                $this->app->log->error("Queue {$code} Loops Failed. {$exception->getMessage()}");
            }
        }

        return ['code' => $code, 'status' => $status, 'message' => $first];
    }
}
