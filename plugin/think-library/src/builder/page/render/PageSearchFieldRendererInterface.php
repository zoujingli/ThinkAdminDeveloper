<?php

declare(strict_types=1);

namespace think\admin\builder\page\render;

/**
 * 页面搜索字段渲染器接口.
 * @class PageSearchFieldRendererInterface
 */
interface PageSearchFieldRendererInterface
{
    /**
     * @param array<string, mixed> $field
     */
    public function render(array $field, PageSearchRenderContext $context): string;
}
