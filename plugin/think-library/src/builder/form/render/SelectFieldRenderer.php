<?php

declare(strict_types=1);

namespace think\admin\builder\form\render;

use think\admin\builder\BuilderLang;

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
        $control .= "\n\t\t\t\t" . sprintf('<option value="">%s</option>', $context->escape(BuilderLang::text('-- 请选择 --')));
        $control .= "\n\t\t\t\t" . $context->renderSelectOptions();
        $control .= "\n\t\t\t" . '</select>';
        return $this->renderFieldShell($context, $control);
    }
}
