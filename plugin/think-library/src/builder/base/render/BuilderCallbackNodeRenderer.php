<?php

declare(strict_types=1);

namespace think\admin\builder\base\render;

/**
 * Builder 回调代理节点渲染基类.
 * @class BuilderCallbackNodeRenderer
 */
abstract class BuilderCallbackNodeRenderer
{
    protected function invoke(callable $callback, mixed ...$args): string
    {
        return strval($callback(...$args));
    }
}
