<?php

declare(strict_types=1);

namespace think\admin\builder\form\component;

use think\admin\builder\BuilderLang;
use think\admin\builder\base\render\BuilderAttributes;

/**
 * 表单组件基础抽象。
 * @class AbstractFormComponent
 */
abstract class AbstractFormComponent implements FormComponentInterface
{
    final public static function make(): static
    {
        return new static();
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    protected function mergeConfig(array $config, array $extra): array
    {
        return array_merge($config, $extra);
    }

    /**
     * @param array<string, mixed> $config
     */
    protected function appendClass(array &$config, string $key, string|array $class): void
    {
        $config[$key] = BuilderAttributes::mergeClassNames(strval($config[$key] ?? ''), $class);
    }

    protected function escape(string $content): string
    {
        return htmlentities(BuilderLang::text($content), ENT_QUOTES, 'UTF-8');
    }
}
