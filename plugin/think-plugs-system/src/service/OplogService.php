<?php

declare(strict_types=1);

namespace plugin\system\service;

use plugin\system\model\SystemOplog;
use think\admin\helper\QueryHelper;
use think\admin\Service;

/**
 * 系统日志服务。
 * @class OplogService
 */
class OplogService extends Service
{
    /**
     * 构建日志列表上下文。
     * @return array<string, mixed>
     */
    public static function buildIndexContext(): array
    {
        $rows = array_values(SystemOplog::mk()->field('action,username')->select()->toArray());
        $users = [];
        $actions = [];
        foreach ($rows as $row) {
            $username = trim(strval($row['username'] ?? ''));
            $action = trim(strval($row['action'] ?? ''));
            if ($username !== '' && !in_array($username, $users, true)) {
                $users[] = $username;
            }
            if ($action !== '' && !in_array($action, $actions, true)) {
                $actions[] = $action;
            }
        }
        sort($users);
        sort($actions);

        return [
            'requestBaseUrl' => request()->baseUrl(),
            'title' => '系统日志管理',
            'users' => $users,
            'actions' => $actions,
        ];
    }

    /**
     * 应用日志列表查询。
     */
    public static function applyIndexQuery(QueryHelper $query): void
    {
        $query->dateBetween('create_time')->equal('username,action')->like('content,node');
        $requestIp = trim(strval(request()->get('request_ip', request()->get('geoip', ''))));
        if ($requestIp !== '') {
            $query->where(function ($builder) use ($requestIp) {
                $builder->whereOr([
                    ['request_ip', 'like', "%{$requestIp}%"],
                    ['geoip', 'like', "%{$requestIp}%"],
                ]);
            });
        }
    }

    /**
     * 填充日志展示字段。
     * @param array<int, array<string, mixed>> $data
     */
    public static function enrichRows(array &$data): void
    {
        $region = new \Ip2Region();
        foreach ($data as &$row) {
            $row = SystemOplog::normalizeRow($row);
            $requestIp = strval($row['request_ip'] ?? '');
            try {
                $row['request_region'] = $requestIp !== '' ? strval($region->simple($requestIp)) : '';
            } catch (\Exception $exception) {
                $row['request_region'] = $exception->getMessage();
            }
        }
        unset($row);
    }
}
