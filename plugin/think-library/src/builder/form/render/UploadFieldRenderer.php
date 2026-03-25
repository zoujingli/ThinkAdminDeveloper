<?php

declare(strict_types=1);

namespace think\admin\builder\form\render;

/**
 * 上传字段渲染器.
 * @class UploadFieldRenderer
 */
class UploadFieldRenderer extends AbstractFormFieldRenderer
{
    private ?FormUploadRuntimeRenderer $runtimeRenderer = null;

    public function render(FormFieldRenderContext $context): string
    {
        return $context->type() === 'images' ? $this->renderMultiple($context) : $this->renderSingle($context);
    }

    private function renderSingle(FormFieldRenderContext $context): string
    {
        $field = $context->field();
        $type = $context->type();
        $attrs = $context->resolveInputAttrs('layui-input layui-bg-gray');
        $attrs['type'] = 'text';
        $attrs['placeholder'] = $attrs['placeholder'] ?? "请上传{$field['title']}";

        $uploadTypes = $this->runtimeRenderer()->resolveUploadTypes($field, $type);
        $body = "\n\t\t\t\t" . sprintf('<input name="%s" %s value="%s">', $field['name'], $context->attrs($attrs), $context->valueExpression());
        $body .= "\n\t\t\t\t" . $this->runtimeRenderer()->renderTrigger($context, $field['name'], $type, $uploadTypes);

        $html = $this->renderFieldShell(
            $context,
            $body . "\n\t\t\t",
            'div',
            'layui-form-item',
            'relative block label-required-null',
            true
        );
        return $this->appendInlineScript($html, $this->runtimeRenderer()->renderInitScript($field, $type));
    }

    private function renderMultiple(FormFieldRenderContext $context): string
    {
        $field = $context->field();
        $attrs = $context->resolveInputAttrs();
        $attrs['type'] = 'hidden';
        $attrs['placeholder'] = $attrs['placeholder'] ?? "请上传{$field['title']} ( 多图 )";

        $body = "\n\t\t\t\t" . sprintf('<input name="%s" %s value="%s">', $field['name'], $context->attrs($attrs), $context->joinedValueExpression()) . "\n\t\t\t";
        return $this->appendInlineScript(
            $this->renderFieldShell(
                $context,
                $body,
                'div',
                'layui-form-item',
                'layui-textarea help-images layui-bg-gray',
                true
            ),
            $this->runtimeRenderer()->renderInitScript($field, 'images')
        );
    }

    private function runtimeRenderer(): FormUploadRuntimeRenderer
    {
        return $this->runtimeRenderer ??= new FormUploadRuntimeRenderer();
    }
}
