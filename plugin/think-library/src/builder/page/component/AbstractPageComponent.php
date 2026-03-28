<?php

declare(strict_types=1);

namespace think\admin\builder\page\component;

use think\admin\builder\BuilderLang;
use think\admin\builder\base\render\BuilderAttributes;

/**
 * 页面组件基础抽象。
 * @class AbstractPageComponent
 */
abstract class AbstractPageComponent implements PageComponentInterface
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

    protected function text(string $content): string
    {
        return BuilderLang::text($content);
    }
}
