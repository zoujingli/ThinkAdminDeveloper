<?php

declare(strict_types=1);

namespace think\admin\builder\page\render;

use think\admin\builder\base\render\BuilderNodeRendererFactory;

/**
 * 页面节点渲染器工厂.
 * @class PageNodeRendererFactory
 */
class PageNodeRendererFactory extends BuilderNodeRendererFactory
{
    /**
     * @param array<string, mixed> $node
     */
    public function create(array $node): PageNodeRendererInterface
    {
        $class = $this->resolveRendererClass($node);
        return new $class();
    }

    protected function rendererMap(): array
    {
        return [
            'html' => PageHtmlNodeRenderer::class,
            'search' => PageSearchNodeRenderer::class,
            'table' => PageTableNodeRenderer::class,
            'element' => PageElementNodeRenderer::class,
        ];
    }

    protected function fallbackRendererClass(): string
    {
        return PageHtmlNodeRenderer::class;
    }
}
