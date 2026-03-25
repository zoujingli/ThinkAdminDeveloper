<?php

declare(strict_types=1);

namespace think\admin\builder\page\render;

/**
 * 页面节点渲染器接口.
 * @class PageNodeRendererInterface
 */
interface PageNodeRendererInterface
{
    /**
     * @param array<string, mixed> $node
     */
    public function render(array $node, PageNodeRenderContext $context): string;
}
