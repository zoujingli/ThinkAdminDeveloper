<?php

declare(strict_types=1);

namespace think\admin\builder\base\render;

/**
 * Builder 渲染状态基类.
 * @class BuilderRenderState
 */
class BuilderRenderState
{
    /**
     * @param array<string, mixed> $schema
     */
    public function __construct(
        private array $schema,
        private BuilderNodeRendererFactory $nodeRendererFactory,
        private BuilderNodeRenderContext $nodeRenderContext,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(): array
    {
        return $this->schema;
    }

    public function nodeRendererFactory(): BuilderNodeRendererFactory
    {
        return $this->nodeRendererFactory;
    }

    public function nodeRenderContext(): BuilderNodeRenderContext
    {
        return $this->nodeRenderContext;
    }
}
