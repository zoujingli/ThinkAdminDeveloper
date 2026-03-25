<?php

declare(strict_types=1);

namespace think\admin\builder\page\render;

use think\admin\builder\base\render\BuilderAttributesRenderContext;

/**
 * 页面表格渲染上下文.
 * @class PageTableRenderContext
 */
class PageTableRenderContext
extends BuilderAttributesRenderContext
{
    /**
     * @param callable(array<string, mixed>): string $attrsRenderer
     */
    public function __construct(
        callable $attrsRenderer,
    ) {
        parent::__construct($attrsRenderer);
    }
}
