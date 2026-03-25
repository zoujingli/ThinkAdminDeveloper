<?php

declare(strict_types=1);

namespace think\admin\builder\form\render;

/**
 * 表单字段渲染器接口.
 * @class FormFieldRendererInterface
 */
interface FormFieldRendererInterface
{
    public function render(FormFieldRenderContext $context): string;
}
