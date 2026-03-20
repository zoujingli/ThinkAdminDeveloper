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

use Throwable;
use think\exception\Handle;

/**
 * Worker-safe exception handler.
 * It normalizes debug payloads so JSON responses never include unsupported trace values.
 */
class WorkerExceptionHandle extends Handle
{
    protected function getDebugMsg(Throwable $exception): array
    {
        return $this->normalizeValue(parent::getDebugMsg($exception));
    }

    private function normalizeValue(mixed $value, int $depth = 0): mixed
    {
        if ($depth >= 8) {
            return '[depth-limit]';
        }

        if ($value === null || is_bool($value) || is_int($value) || is_float($value) || is_string($value)) {
            return $value;
        }

        if (is_resource($value)) {
            return sprintf('[resource(%s)]', get_resource_type($value));
        }

        if (is_array($value)) {
            $normalized = [];
            $count = 0;
            foreach ($value as $key => $item) {
                if (++$count > 200) {
                    $normalized['...'] = '[truncated]';
                    break;
                }
                $normalized[$key] = $this->normalizeValue($item, $depth + 1);
            }

            return $normalized;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if ($value instanceof \JsonSerializable) {
            try {
                return $this->normalizeValue($value->jsonSerialize(), $depth + 1);
            } catch (\Throwable) {
                return sprintf('[object(%s)]', $value::class);
            }
        }

        if ($value instanceof \Stringable) {
            return (string)$value;
        }

        if ($value instanceof \Closure) {
            return '[closure]';
        }

        if (is_object($value)) {
            return sprintf('[object(%s)]', $value::class);
        }

        return sprintf('[%s]', gettype($value));
    }
}
