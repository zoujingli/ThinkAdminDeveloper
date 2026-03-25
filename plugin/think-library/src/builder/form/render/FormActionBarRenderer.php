<?php

declare(strict_types=1);

namespace think\admin\builder\form\render;

use think\admin\builder\base\render\BuilderElementNodeRenderer;

/**
 * 表单动作条渲染器.
 * @class FormActionBarRenderer
 */
class FormActionBarRenderer extends BuilderElementNodeRenderer implements FormNodeRendererInterface
{
    /**
     * @param array<string, mixed> $node
     */
    public function render(array $node, FormNodeRenderContext $context): string
    {
        $attrs = is_array($node['attrs'] ?? null) ? $node['attrs'] : [];
        $children = is_array($node['children'] ?? null) ? $node['children'] : [];

        $html = '<div class="hr-line-dashed"></div>';
        if ($identity = $context->renderIdentityField()) {
            $html .= "\n\t\t" . $identity;
        }
        $html .= "\n\t\t" . $this->wrapElement('div', $attrs, "\n\t\t\t" . $context->renderChildren($children) . "\n\t\t", $context);
        return $html;
    }
}
