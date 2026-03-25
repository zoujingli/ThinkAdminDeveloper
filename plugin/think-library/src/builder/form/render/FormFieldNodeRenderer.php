<?php

declare(strict_types=1);

namespace think\admin\builder\form\render;

use think\admin\builder\base\render\BuilderCallbackNodeRenderer;

/**
 * 表单字段节点渲染器.
 * @class FormFieldNodeRenderer
 */
class FormFieldNodeRenderer extends BuilderCallbackNodeRenderer implements FormNodeRendererInterface
{
    public function render(array $node, FormNodeRenderContext $context): string
    {
        if (!is_array($node['field'] ?? null)) {
            return '';
        }
        $field = $node['field'];
        $field['container_attrs'] = is_array($node['attrs'] ?? null) ? $node['attrs'] : [];
        $field['container_modules'] = is_array($node['modules'] ?? null) ? $node['modules'] : [];
        $field['parts'] = is_array($node['parts'] ?? null) ? $node['parts'] : [];
        return $this->invoke([$context, 'renderField'], $field);
    }
}
