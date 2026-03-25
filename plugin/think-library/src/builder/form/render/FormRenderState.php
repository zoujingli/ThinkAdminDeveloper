<?php

declare(strict_types=1);

namespace think\admin\builder\form\render;

use think\admin\builder\base\render\BuilderRenderState;

/**
 * 表单渲染状态对象.
 * @class FormRenderState
 */
class FormRenderState extends BuilderRenderState
{
    /**
     * @param array<string, mixed> $schema
     */
    public function __construct(
        array $schema,
        FormNodeRendererFactory $nodeRendererFactory,
        FormNodeRenderContext $nodeRenderContext,
    ) {
        parent::__construct($schema, $nodeRendererFactory, $nodeRenderContext);
    }

    public function nodeRendererFactory(): FormNodeRendererFactory
    {
        return parent::nodeRendererFactory();
    }

    public function nodeRenderContext(): FormNodeRenderContext
    {
        return parent::nodeRenderContext();
    }
}
