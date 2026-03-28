<?php

declare(strict_types=1);

namespace plugin\system\service;

use plugin\worker\model\SystemQueue;
use plugin\worker\service\ProcessService;
use think\admin\helper\QueryHelper;
use think\admin\Service;

/**
 * 系统任务服务。
 * @class QueueService
 */
class QueueService extends Service
{
    /**
     * 队列状态统计映射。
     * @var array<int, string>
     */
    private const STATUS_SUMMARY_MAP = [1 => 'pre', 2 => 'dos', 3 => 'oks', 4 => 'ers'];

    /**
     * 构建任务列表上下文。
     * @return array<string, mixed>
     */
    public static function buildIndexContext(): array
    {
        $isWin = ProcessService::isWin();
        $super = AuthService::isSuper();

        return [
            'title' => '系统任务管理',
            'requestBaseUrl' => request()->baseUrl(),
            'iswin' => $isWin,
            'super' => $super,
            'command' => self::buildStartCommand($super, $isWin),
        ];
    }

    /**
     * 应用任务列表查询。
     */
    public static function applyIndexQuery(QueryHelper $query): void
    {
        $query->equal('status')->like('code|title#title,command');
        $query->timeBetween('enter_time,exec_time')->dateBetween('create_time');
    }

    /**
     * 填充任务状态统计。
     * @param array<string, mixed> $result
     */
    public static function enrichPageResult(array &$result): void
    {
        $summary = ['dos' => 0, 'pre' => 0, 'oks' => 0, 'ers' => 0];
        $rows = SystemQueue::mk()->field('status,count(1) count')->group('status')->select()->toArray();
        foreach ($rows as $row) {
            $status = intval($row['status'] ?? 0);
            if (isset(self::STATUS_SUMMARY_MAP[$status])) {
                $summary[self::STATUS_SUMMARY_MAP[$status]] = intval($row['count'] ?? 0);
            }
        }
        $result['extra'] = $summary;
    }

    /**
     * 生成队列启动命令。
     */
    private static function buildStartCommand(bool $super, bool $isWin): string
    {
        if (!$super) {
            return '';
        }

        $command = ProcessService::think(ProcessService::workerCommand('start', 'queue', true));
        $user = trim(strval($_SERVER['USER'] ?? ''));
        if (!$isWin && $user !== '') {
            return "sudo -u {$user} {$command}";
        }

        return $command;
    }
}
