<?php

declare(strict_types=1);

namespace think\admin\builder\form\render;

/**
 * 下拉字段渲染器.
 * @class SelectFieldRenderer
 */
class SelectFieldRenderer extends AbstractFormFieldRenderer
{
    public function render(FormFieldRenderContext $context): string
    {
        $field = $context->field();
        $attrs = $context->resolveInputAttrs('layui-select');
        $attrs['name'] = $field['name'];

        $control = sprintf('<select %s>', $context->attrs($attrs));
        $control .= "\n\t\t\t\t" . '<option value="">-- 请选择 --</option>';
        $control .= "\n\t\t\t\t" . $context->renderSelectOptions();
        $control .= "\n\t\t\t" . '</select>';
        return $this->renderFieldShell($context, $control);
    }
}
