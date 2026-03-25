<?php

declare(strict_types=1);

namespace think\admin\builder\page\render;

/**
 * 页面表格渲染器.
 * @class PageTableRenderer
 */
class PageTableRenderer
{
    /**
     * @param array<string, mixed> $attrs
     */
    public function render(array $attrs, PageTableRenderContext $context): string
    {
        return sprintf('<table %s></table>', $context->attrs($attrs));
    }
}
