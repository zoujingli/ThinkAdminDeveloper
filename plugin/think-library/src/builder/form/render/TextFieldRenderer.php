<?php

declare(strict_types=1);

namespace think\admin\builder\form\render;

use think\admin\builder\BuilderLang;

/**
 * 文本字段渲染器.
 * @class TextFieldRenderer
 */
class TextFieldRenderer extends AbstractFormFieldRenderer
{
    public function render(FormFieldRenderContext $context): string
    {
        $field = $context->field();
        if ($context->type() === 'textarea') {
            $attrs = $context->resolveInputAttrs('layui-textarea');
            $attrs['placeholder'] = $attrs['placeholder'] ?? BuilderLang::format('请输入%s', [strval($field['title'])]);
            return $this->renderFieldShell(
                $context,
                sprintf('<textarea name="%s" %s>%s</textarea>', $field['name'], $context->attrs($attrs), $context->valueExpression())
            );
        }

        $attrs = $context->resolveInputAttrs('layui-input');
        if ($context->type() !== 'text' && !isset($attrs['type'])) {
            $attrs['type'] = $context->type();
        }
        $attrs['placeholder'] = $attrs['placeholder'] ?? BuilderLang::format('请输入%s', [strval($field['title'])]);
        $control = sprintf('<input name="%s" %s value="%s">', $field['name'], $context->attrs($attrs), $context->valueExpression());
        $addon = $context->renderInputContent();
        if ($addon !== '') {
            $control .= $addon;
        }
        return $this->renderFieldShell($context, $control, 'label', 'layui-form-item block relative', $addon !== '' ? 'relative' : '', $addon !== '');
    }
}
