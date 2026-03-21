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

namespace plugin\system\controller\api;

use plugin\system\service\SystemAuthService;
use plugin\worker\model\SystemQueue;
use Psr\Log\NullLogger;
use think\admin\Controller;
use think\exception\HttpResponseException;

/**
 * 任务监听服务管理.
 * @class Queue
 */
class Queue extends Controller
{
    /**
     * 停止监听服务
     * @login true
     */
    public function stop()
    {
        if (SystemAuthService::isSuper()) {
            try {
                $message = $this->app->console->call('xadmin:worker', ['stop', 'queue'])->fetch();
                if (stripos($message, 'stop signal sent') !== false) {
                    sysoplog('系统运维管理', '尝试停止任务监听服务');
                    $this->success('停止任务监听服务成功！');
                } elseif (stripos($message, 'is not running') !== false) {
                    $this->success('没有找到需要停止的服务！');
                } else {
                    $this->error(nl2br($message));
                }
            } catch (HttpResponseException $exception) {
                throw $exception;
            } catch (\Exception $exception) {
                trace_file($exception);
                $this->error($exception->getMessage());
            }
        } else {
            $this->error('请使用超管账号操作！');
        }
    }

    /**
     * 启动监听服务
     * @login true
     */
    public function start()
    {
        if (SystemAuthService::isSuper()) {
            try {
                $message = $this->app->console->call('xadmin:worker', ['start', 'queue', '--daemon'])->fetch();
                if (stripos($message, 'started successfully for pid') !== false) {
                    sysoplog('系统运维管理', '尝试启动任务监听服务');
                    $this->success('任务监听服务启动成功！');
                } elseif (stripos($message, 'already running for pid') !== false) {
                    $this->success('任务监听服务已经启动！');
                } else {
                    $this->error(nl2br($message));
                }
            } catch (HttpResponseException $exception) {
                throw $exception;
            } catch (\Exception $exception) {
                trace_file($exception);
                $this->error($exception->getMessage());
            }
        } else {
            $this->error('请使用超管账号操作！');
        }
    }

    /**
     * 检查监听服务
     * @login true
     */
    public function status()
    {
        if (SystemAuthService::isSuper()) {
            try {
                $message = $this->app->console->call('xadmin:worker', ['status', 'queue'])->fetch();
                if (preg_match('/process.*?\d+.*?running/i', $message)) {
                    echo "<span class='color-green pointer' data-tips-text='{$message}'>{$this->app->lang->get('已启动')}</span>";
                } else {
                    echo "<span class='color-red pointer' data-tips-text='{$message}'>{$this->app->lang->get('未启动')}</span>";
                }
            } catch (\Error|\Exception $exception) {
                echo "<span class='color-red pointer' data-tips-text='{$exception->getMessage()}'>{$this->app->lang->get('异 常')}</span>";
            }
        } else {
            $message = lang('只有超级管理员才能操作！');
            echo "<span class='color-red pointer' data-tips-text='{$message}'>{$this->app->lang->get('无权限')}</span>";
        }
    }

    /**
     * 查询任务进度.
     * @login true
     */
    public function progress()
    {
        $input = $this->_vali(['code.require' => '任务编号不能为空！']);
        $this->app->db->setLog(new NullLogger()); /* 关闭数据库请求日志 */
        $queue = SystemQueue::mk()->where($input)->field(['code', 'status', 'exec_desc', 'message'])->findOrEmpty();

        $data = [
            'code' => $input['code'],
            'status' => 0,
            'message' => '>>> 等待任务状态更新 <<<',
            'progress' => '0.00',
            'history' => [],
        ];

        if ($queue->isExists()) {
            $snapshot = json_decode((string)$queue->getAttr('message'), true);
            $status = is_array($snapshot) && array_key_exists('status', $snapshot)
                ? $snapshot['status']
                : intval($queue->getAttr('status'));
            $runtimeStatus = is_numeric($status) ? intval($status) : intval($queue->getAttr('status'));
            $progress = is_array($snapshot) && array_key_exists('progress', $snapshot)
                ? $snapshot['progress']
                : ($runtimeStatus === 3 ? '100.00' : '0.00');
            $history = is_array($snapshot['history'] ?? null) ? array_values($snapshot['history']) : [];
            $message = trim(strval($snapshot['message'] ?? ''));

            if ($message === '') {
                $message = $this->defaultProgressMessage($runtimeStatus, trim(strval($queue->getAttr('exec_desc') ?: '')));
            }

            if ($history === [] && $message !== '') {
                $history[] = [
                    'message' => $message,
                    'progress' => $progress,
                    'datetime' => date('Y-m-d H:i:s'),
                ];
            }

            $data = [
                'code' => strval($queue->getAttr('code') ?: $input['code']),
                'status' => $status,
                'message' => $message,
                'progress' => $progress,
                'history' => $history,
            ];
        }

        $this->success('获取任务进度成功！', $data);
    }

    protected function defaultProgressMessage(int $status, string $execDesc = ''): string
    {
        if ($execDesc !== '') {
            return ">>> {$execDesc} <<<";
        }

        return match ($status) {
            1 => '>>> 任务等待执行 <<<',
            2 => '>>> 任务处理中 <<<',
            3 => '>>> 任务处理完成 <<<',
            4 => '>>> 任务处理失败 <<<',
            default => '>>> 等待任务状态更新 <<<',
        };
    }
}
