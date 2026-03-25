<?php

declare(strict_types=1);

namespace think\admin\builder\form\render;

use think\admin\builder\base\render\InlineScriptRenderer;

/**
 * 表单字段渲染基类.
 * @class AbstractFormFieldRenderer
 */
abstract class AbstractFormFieldRenderer implements FormFieldRendererInterface
{
    protected function renderFieldShell(
        FormFieldRenderContext $context,
        string $control,
        string $containerTag = 'label',
        string $containerClass = 'layui-form-item block relative',
        string $bodyClass = '',
        bool $alwaysWrapBody = false
    ): string {
        $html = "\n\t\t" . $context->openContainer($containerTag, $containerClass);
        $html .= "\n\t\t\t" . $context->renderLabel();
        $html .= "\n\t\t\t" . $context->renderBody($control, $bodyClass, $alwaysWrapBody);
        if ($remark = $context->renderRemark()) {
            $html .= "\n\t\t\t" . $remark;
        }
        return "{$html}\n\t\t</{$containerTag}>";
    }

    protected function appendInlineScript(string $html, string $script): string
    {
        return $html . (new InlineScriptRenderer())->render([$script]);
    }
}
