<?php

declare(strict_types=1);

namespace think\admin\builder\page\render;

/**
 * 页面脚本渲染上下文.
 * @class PageScriptRenderContext
 */
class PageScriptRenderContext
{
    /**
     * @param callable(mixed): string $jsEncoder
     */
    public function __construct(
        private $jsEncoder,
    ) {
    }

    public function encodeJs(mixed $value): string
    {
        return ($this->jsEncoder)($value);
    }
}
