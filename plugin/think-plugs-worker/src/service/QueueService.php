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
use think\admin\contract\QueueManagerInterface;
use think\admin\contract\QueueRuntimeInterface;
use think\admin\Exception;
use think\admin\extend\CodeToolkit;
use think\admin\service\ProcessService;
use think\admin\service\Service;
use think\Model;

/**
 * Queue runtime service backed by the system_queue table.
 */
class QueueService extends Service implements QueueManagerInterface, QueueHandlerInterface
{
    public const STATE_WAIT = 1;

    public const STATE_LOCK = 2;

    public const STATE_DONE = 3;

    public const STATE_ERROR = 4;

    private const CONTEXT_ACTIVE = 'think.admin.queue.active';

    private const CONTEXT_CODE = 'think.admin.queue.code';

    public string $code = '';

    public string $title = '';

    public array $data = [];

    public SystemQueue $record;

    /** @var null|string[] */
    private static ?array $tableFields = null;

    /** @var array<string, mixed> */
    private array $msgs = [];

    private bool $msgsWriteDb = false;

    private int $tryTimes = 0;

    /**
     * @throws Exception
     */
    public function initialize(string $code = ''): self
    {
        if ($this->code !== '' && $this->code !== $code) {
            $this->lazyWrite(true);
            $this->msgs = [];
        }

        if ($code !== '') {
            $this->record = SystemQueue::mk()->master()->where(['code' => $code])->findOrEmpty();
            if ($this->record->isEmpty()) {
                $message = sprintf('队列初始化失败，任务 %s 不存在。', $code);
                $this->app->log->error($message);
                throw new Exception($message);
            }

            $this->code = $code;
            $this->data = json_decode((string)$this->record['exec_data'], true) ?: [];
            $this->title = strval($this->record['title']);
        }

        $this->msgsWriteDb = static::hasField('message');
        return $this;
    }

    public static function enterContext(string $code): void
    {
        sysvar(self::CONTEXT_ACTIVE, true);
        sysvar(self::CONTEXT_CODE, $code);
    }

    public static function leaveContext(): void
    {
        sysvar(self::CONTEXT_ACTIVE, false);
        sysvar(self::CONTEXT_CODE, '');
    }

    public static function inContext(?string $code = null): bool
    {
        $current = static::currentCode();
        if ($code !== null) {
            return $current !== '' && $current === $code;
        }

        return boolval(sysvar(self::CONTEXT_ACTIVE)) && $current !== '';
    }

    public static function currentCode(): string
    {
        return trim(strval(sysvar(self::CONTEXT_CODE) ?: ''));
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getRecord(): Model
    {
        return $this->record;
    }

    public function handle(QueueRuntimeInterface $queue): mixed
    {
        return $this->initialize($queue->getCode())->execute($queue->getData());
    }

    /**
     * @throws Exception
     */
    public function reset(int $wait = 0): self
    {
        if ($this->record->isEmpty()) {
            $message = "队列重置失败，任务 {$this->code} 数据不能为空。";
            $this->app->log->error($message);
            throw new Exception($message);
        }

        $payload = [
            'exec_pid' => 0,
            'exec_time' => time() + max(0, $wait),
            'exec_desc' => '',
            'enter_time' => 0,
            'outer_time' => 0,
            'status' => static::STATE_WAIT,
        ];
        if (static::hasField('message')) {
            $payload['message'] = null;
        }

        $this->record->save($payload);
        $this->replaceProgress(static::STATE_WAIT, '>>> 任务等待执行 <<<', '0.00');

        return $this;
    }

    /**
     * @throws Exception
     */
    public static function addCleanQueue(int $loops = 3600): self
    {
        return static::register('定时清理系统任务数据', 'xadmin:queue clean', 0, [], $loops);
    }

    /**
     * @throws Exception
     */
    public static function register(string $title, string $command, int $later = 0, array $data = [], int $loops = 0, ?int $legacyLoops = null): self
    {
        try {
            $loops = max(0, intval($legacyLoops ?? ($loops === 1 ? 0 : $loops)));
            $execHash = static::buildExecHash($title, $command, $data, $loops);
            $map = [['status', 'in', [static::STATE_WAIT, static::STATE_LOCK]]];
            if (static::hasField('exec_hash')) {
                $map[] = ['exec_hash', '=', $execHash];
            } else {
                $map[] = ['title', '=', $title];
                $map[] = ['command', '=', $command];
                $map[] = $loops > 0 ? ['loops_time', '>', 0] : ['loops_time', '=', 0];
            }

            if (($queue = SystemQueue::mk()->master()->where($map)->findOrEmpty())->isExists()) {
                throw new Exception('相同类型的任务已在等待或执行中。', 0, $queue['code']);
            }

            do {
                $map = ['code' => $code = CodeToolkit::uniqidDate(16, 'Q')];
            } while (($queue = SystemQueue::mk()->master()->where($map)->findOrEmpty())->isExists());

            $payload = [
                'code' => $code,
                'title' => $title,
                'command' => $command,
                'attempts' => 0,
                'exec_data' => json_encode($data, JSON_UNESCAPED_UNICODE),
                'exec_time' => $later > 0 ? time() + $later : time(),
                'enter_time' => 0,
                'outer_time' => 0,
                'loops_time' => $loops,
                'create_time' => date('Y-m-d H:i:s'),
            ];
            if (static::hasField('exec_hash')) {
                $payload['exec_hash'] = $execHash;
            }

            $queue->save($payload);
            $that = static::instance([], true)->initialize($code);
            $that->progress(static::STATE_WAIT, '>>> 任务创建成功 <<<', '0.00');

            return $that;
        } catch (Exception $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
    }

    public function registerTask(string $title, string $command, int $later = 0, array $data = [], int $loops = 0, ?int $legacyLoops = null): QueueManagerInterface
    {
        return static::register($title, $command, $later, $data, $loops, $legacyLoops);
    }

    public function getCurrentCode(): string
    {
        return static::currentCode();
    }

    public function isInContext(?string $code = null): bool
    {
        return static::inContext($code);
    }

    public static function buildExecHash(string $title, string $command, array $data = [], int $loops = 0): string
    {
        return sha1(json_encode([
            'title' => trim($title),
            'command' => trim($command),
            'mode' => $loops > 0 ? 'loop' : 'once',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION));
    }

    /**
     * @return array<string, mixed>
     * @throws Exception
     */
    public function progress(?int $status = null, ?string $message = null, ?string $progress = null, int $backline = 0): array
    {
        if (is_numeric($status) && intval($status) === static::STATE_DONE) {
            $progress ??= '100.00';
            $message ??= '>>> 任务已经完成 <<<';
        }

        if (is_numeric($status) && intval($status) === static::STATE_ERROR) {
            $progress ??= '0.00';
            $message ??= '>>> 任务执行失败 <<<';
        }

        try {
            if ($this->msgs === []) {
                $this->msgs = $this->app->cache->get($this->progressCacheKey(), [
                    'code' => $this->code,
                    'status' => $status,
                    'sctime' => 0,
                    'message' => $message,
                    'progress' => $progress,
                    'history' => [],
                ]);
            }
            $this->tryTimes = 0;
        } catch (\Error|\Exception) {
            if ($this->tryTimes++ > 10) {
                throw new Exception('读取任务进度缓存失败。');
            }

            return $this->progress($status, $message, $progress, $backline);
        }

        while (--$backline > -1 && count($this->msgs['history']) > 0) {
            array_pop($this->msgs['history']);
        }

        if (is_numeric($status)) {
            $this->msgs['status'] = intval($status);
        }

        if (is_numeric($progress)) {
            $progress = str_pad(sprintf('%.2f', $progress), 6, '0', STR_PAD_LEFT);
        }

        if (is_string($message) && $progress === null) {
            $this->msgs['swrite'] = 0;
            $this->msgs['message'] = $message;
            $this->msgs['history'][] = [
                'message' => $message,
                'progress' => $this->msgs['progress'] ?? null,
                'datetime' => date('Y-m-d H:i:s'),
            ];
        } elseif ($message === null && is_numeric($progress)) {
            $this->msgs['swrite'] = 0;
            $this->msgs['progress'] = $progress;
            $this->msgs['history'][] = [
                'message' => $this->msgs['message'] ?? '',
                'progress' => $progress,
                'datetime' => date('Y-m-d H:i:s'),
            ];
        } elseif (is_string($message) && is_numeric($progress)) {
            $this->msgs['swrite'] = 0;
            $this->msgs['message'] = $message;
            $this->msgs['progress'] = $progress;
            $this->msgs['history'][] = [
                'message' => $message,
                'progress' => $progress,
                'datetime' => date('Y-m-d H:i:s'),
            ];
        }

        if ((is_string($message) || is_numeric($progress)) && count($this->msgs['history']) > 10) {
            $this->msgs['history'] = array_slice($this->msgs['history'], -10);
        }

        return $this->lazyWrite();
    }

    /**
     * @throws Exception
     */
    public function message(int $total, int $count, string $message = '', int $backline = 0): void
    {
        $prefix = str_pad((string)$count, strlen((string)$total), '0', STR_PAD_LEFT);

        if (static::inContext($this->code)) {
            $this->progress(
                static::STATE_LOCK,
                "[{$prefix}/{$total}] {$message}",
                sprintf('%.2f', $count / max($total, 1) * 100),
                $backline
            );
            return;
        }

        ProcessService::message("[{$prefix}/{$total}] {$message}", $backline);
    }

    /**
     * @throws Exception
     */
    public function success(string $message): void
    {
        throw new Exception($message, static::STATE_DONE, $this->code);
    }

    /**
     * @throws Exception
     */
    public function error(string $message): void
    {
        throw new Exception($message, static::STATE_ERROR, $this->code);
    }

    public function execute(array $data = []) {}

    /**
     * @return array<string, mixed>
     */
    private function lazyWrite(bool $force = false): array
    {
        if (!isset($this->msgs['status'])) {
            return $this->msgs;
        }

        if ($force || empty($this->msgs['sctime']) || in_array($this->msgs['status'], [static::STATE_DONE, static::STATE_ERROR], true) || microtime(true) - floatval($this->msgs['sctime']) > 1) {
            if (empty($this->msgs['swrite']) && $this->record->isExists()) {
                [$this->msgs['swrite'], $this->msgs['sctime']] = [1, microtime(true)];
                $this->app->cache->set($this->progressCacheKey(), $this->msgs, 864000);

                if ($this->msgsWriteDb) {
                    $this->record->save(['message' => json_encode($this->msgs, JSON_UNESCAPED_UNICODE)]);
                }
            }
        }

        return $this->msgs;
    }

    private function replaceProgress(?int $status = null, ?string $message = null, ?string $progress = null): void
    {
        $this->msgs = [];
        $this->app->cache->delete($this->progressCacheKey());

        if ($this->msgsWriteDb && isset($this->record) && $this->record->isExists()) {
            $this->record->save(['message' => null]);
        }

        if ($status !== null || $message !== null || $progress !== null) {
            $this->progress($status, $message, $progress);
        }
    }

    private function progressCacheKey(): string
    {
        return "queue_{$this->code}_progress";
    }

    /**
     * @return string[]
     */
    private static function tableFields(): array
    {
        return self::$tableFields ??= SystemQueue::mk()->getTableFields();
    }

    private static function hasField(string $field): bool
    {
        return in_array($field, static::tableFields(), true);
    }
}
