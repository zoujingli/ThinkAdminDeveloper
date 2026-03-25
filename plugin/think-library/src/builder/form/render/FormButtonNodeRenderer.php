<?php

declare(strict_types=1);

namespace think\admin\builder\form\render;

use think\admin\builder\base\render\BuilderHtmlNodeRenderer;

/**
 * 表单按钮节点渲染器.
 * @class FormButtonNodeRenderer
 */
class FormButtonNodeRenderer extends BuilderHtmlNodeRenderer implements FormNodeRendererInterface
{
    public function render(array $node, FormNodeRenderContext $context): string
    {
        return $this->renderHtml($node);
    }
}
