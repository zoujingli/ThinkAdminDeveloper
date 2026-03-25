<?php

declare(strict_types=1);

namespace think\admin\builder\base\render;

/**
 * Builder 节点渲染通用上下文.
 * @class BuilderNodeRenderContext
 */
abstract class BuilderNodeRenderContext
extends BuilderAttributesRenderContext
{
    /**
     * @param callable(array<int, array<string, mixed>>): string $contentRenderer
     * @param callable(array<string, mixed>): string $attrsRenderer
     */
    public function __construct(
        private $contentRenderer,
        callable $attrsRenderer,
    ) {
        parent::__construct($attrsRenderer);
    }

    /**
     * @param array<int, array<string, mixed>> $nodes
     */
    public function renderChildren(array $nodes): string
    {
        return ($this->contentRenderer)($nodes);
    }
}
