<?php

declare(strict_types=1);

namespace think\admin\builder\form\render;

/**
 * 单选复选字段渲染器.
 * @class ChoiceFieldRenderer
 */
class ChoiceFieldRenderer extends AbstractFormFieldRenderer
{
    public function render(FormFieldRenderContext $context): string
    {
        $field = $context->field();
        if (strval($field['vname'] ?? '') === '' && count((array)($field['options'] ?? [])) < 1) {
            throw new \InvalidArgumentException('FormBuilder 复选或单选字段需要提供 vname 或 options');
        }

        $type = $context->type();
        $attrs = $context->resolveInputAttrs();
        $attrs['type'] = $type;
        $attrs['lay-ignore'] = null;
        $attrs['name'] = $field['name'] . ($type === 'checkbox' ? '[]' : '');
        return $this->renderFieldShell(
            $context,
            $context->renderChoiceOptions($attrs, $type) . "\n\t\t\t",
            'div',
            'layui-form-item',
            'layui-textarea help-checks layui-bg-gray',
            true
        );
    }
}
