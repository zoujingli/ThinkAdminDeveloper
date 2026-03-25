<?php

declare(strict_types=1);

namespace think\admin\builder\form\render;

/**
 * 表单节点渲染器接口.
 * @class FormNodeRendererInterface
 */
interface FormNodeRendererInterface
{
    /**
     * @param array<string, mixed> $node
     */
    public function render(array $node, FormNodeRenderContext $context): string;
}
