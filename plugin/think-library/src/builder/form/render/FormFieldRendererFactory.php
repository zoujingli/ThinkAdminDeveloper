<?php

declare(strict_types=1);

namespace think\admin\builder\form\render;

/**
 * 表单字段渲染器工厂.
 * @class FormFieldRendererFactory
 */
class FormFieldRendererFactory
{
    /**
     * @param array<string, mixed> $field
     */
    public function create(array $field): FormFieldRendererInterface
    {
        return match ($field['type'] ?? 'text') {
            'select' => new SelectFieldRenderer(),
            'checkbox', 'radio' => new ChoiceFieldRenderer(),
            'image', 'video', 'images' => new UploadFieldRenderer(),
            default => new TextFieldRenderer(),
        };
    }
}
