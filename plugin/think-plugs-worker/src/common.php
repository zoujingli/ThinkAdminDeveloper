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
use think\admin\Exception;
use think\admin\service\QueueService;

if (!function_exists('sysqueue')) {
    /**
     * 注册异步处理任务
     * @param string $title 任务名称
     * @param string $command 执行内容
     * @param int $later 延时执行时间
     * @param array $data 任务附加数据
     * @param int $loops 循环等待时间
     * @param ?int $legacyLoops 兼容旧调用的循环等待时间参数
     * @throws Exception
     */
    function sysqueue(string $title, string $command, int $later = 0, array $data = [], int $loops = 0, ?int $legacyLoops = null): string
    {
        return QueueService::register($title, $command, $later, $data, $loops, $legacyLoops)->getCode();
    }
}

if (!function_exists('worker_auth_debug_enabled')) {
    /**
     * 判断是否启用 Worker 登录链路调试。
     */
    function worker_auth_debug_enabled(): bool
    {
        $value = env('WORKER_HTTP_DEBUG', '');
        if ($value === '' && function_exists('config')) {
            $value = config('worker.auth_debug', false);
        }

        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return intval($value) > 0;
        }

        return in_array(strtolower(trim((string)$value)), ['1', 'on', 'true', 'yes'], true);
    }
}

if (!function_exists('worker_auth_trace_id')) {
    /**
     * 读写当前请求的调试追踪编号。
     */
    function worker_auth_trace_id(?string $traceId = null): string
    {
        static $current = '';

        if ($traceId !== null) {
            $current = trim($traceId);
        }
        if ($current === '') {
            $current = substr(md5(uniqid('worker-auth-', true) . getmypid()), 0, 12);
        }

        return $current;
    }
}

if (!function_exists('worker_auth_token_snapshot')) {
    /**
     * 生成认证令牌的脱敏摘要，便于跨请求比对。
     */
    function worker_auth_token_snapshot(?string $value): array
    {
        $value = trim((string)$value);
        if ($value === '') {
            return ['len' => 0, 'sha1' => '', 'preview' => ''];
        }

        $length = strlen($value);
        if ($length <= 24) {
            $preview = $value;
        } else {
            $preview = substr($value, 0, 12) . '...' . substr($value, -8);
        }

        return [
            'len' => $length,
            'sha1' => substr(sha1($value), 0, 16),
            'preview' => $preview,
        ];
    }
}

if (!function_exists('worker_auth_should_debug')) {
    /**
     * 判断当前请求是否属于登录态排障范围。
     *
     * @param array<string, mixed> $cookies
     * @param array<string, mixed> $headers
     */
    function worker_auth_should_debug(string $path = '', array $cookies = [], array $headers = []): bool
    {
        if (!worker_auth_debug_enabled()) {
            return false;
        }

        $path = '/' . ltrim(strtok($path, '?') ?: '', '/');
        if (str_contains($path, '/system/login') || str_contains($path, '/system/index')) {
            return true;
        }

        $headers = array_change_key_case($headers, CASE_LOWER);
        if (!empty($headers['authorization'])) {
            return true;
        }

        return isset($cookies['system_access_token']) || isset($cookies['account_access_token']);
    }
}

if (!function_exists('worker_auth_debug')) {
    /**
     * 记录 Worker 登录链路调试日志。
     *
     * @param array<string, mixed> $context
     */
    function worker_auth_debug(string $stage, array $context = []): void
    {
        if (!worker_auth_debug_enabled()) {
            return;
        }

        $file = runpath('runtime/worker/auth-debug.log');
        is_dir($dir = dirname($file)) || @mkdir($dir, 0777, true);

        $record = [
            'time' => sprintf('%.6f', microtime(true)),
            'pid' => getmypid(),
            'trace' => worker_auth_trace_id(),
            'stage' => $stage,
            'context' => $context,
        ];

        @file_put_contents(
            $file,
            json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }
}
