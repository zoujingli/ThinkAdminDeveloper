<?php

declare(strict_types=1);

namespace think\admin\builder\form\render;

use think\admin\builder\base\render\BuilderElementNodeRenderer;

/**
 * 表单普通元素节点渲染器.
 * @class FormElementNodeRenderer
 */
class FormElementNodeRenderer extends BuilderElementNodeRenderer implements FormNodeRendererInterface
{
    public function render(array $node, FormNodeRenderContext $context): string
    {
        return $this->renderElement($node, $context);
    }
}
