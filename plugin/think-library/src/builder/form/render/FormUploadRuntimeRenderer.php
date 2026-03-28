<?php

declare(strict_types=1);

namespace think\admin\builder\form\render;

use think\admin\builder\base\render\BuilderAttributes;

/**
 * 表单上传字段运行时渲染器.
 * @class FormUploadRuntimeRenderer
 */
class FormUploadRuntimeRenderer
{
    /**
     * @param array<string, mixed> $field
     */
    public function resolveDisplayMode(array $field, string $type): string
    {
        if ($type !== 'image') {
            return 'input';
        }
        $mode = strtolower(trim(strval($field['upload']['display'] ?? '')));
        return in_array($mode, ['input', 'preview'], true) ? $mode : 'input';
    }

    /**
     * @param array<string, mixed> $field
     */
    public function resolveUploadTypes(array $field, string $type): string
    {
        return strval($field['upload']['types'] ?? ($type === 'image' ? 'gif,png,jpg,jpeg' : 'mp4'));
    }

    public function renderTrigger(FormFieldRenderContext $context, string $fieldName, string $type, string $uploadTypes): string
    {
        $field = $context->field();
        $trigger = is_array($field['upload']['trigger'] ?? null) ? $field['upload']['trigger'] : [];
        $attrs = is_array($trigger['attrs'] ?? null) ? $trigger['attrs'] : [];
        $icon = trim(strval($trigger['icon'] ?? 'layui-icon-upload'));
        $class = BuilderAttributes::mergeClassNames("layui-icon {$icon} input-right-icon", $trigger['class'] ?? '');
        $attrs = BuilderAttributes::make($attrs)
            ->merge([
                'data-field' => $fieldName,
                'data-type' => $uploadTypes,
            ])
            ->class($class)
            ->all();

        if (array_key_exists('file', $trigger)) {
            $attrs = $this->applyFileAttr($attrs, $trigger['file']);
        } elseif ($type === 'image') {
            $attrs['data-file'] = 'image';
        } else {
            $attrs['data-file'] = null;
        }

        return sprintf('<a %s></a>', $context->attrs($attrs));
    }

    /**
     * @param array<string, mixed> $field
     */
    public function renderInitScript(array $field, string $type): string
    {
        $runtime = is_array($field['upload']['runtime'] ?? null) ? $field['upload']['runtime'] : [];
        $display = $this->resolveDisplayMode($field, $type);
        $name = strval($field['name'] ?? '');
        $selector = trim(strval($runtime['selector'] ?? sprintf('input[name="%s"]', $name)));
        $method = trim(strval($runtime['method'] ?? $this->runtimeMethod($type, $display)));
        if ($selector === '' || $method === '') {
            return '';
        }
        return sprintf('$(%s).%s()', json_encode($selector, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '""', $method);
    }

    private function runtimeMethod(string $type, string $display): string
    {
        return match ($type) {
            'image' => $display === 'preview' ? 'uploadOneImage' : '',
            'video' => 'uploadOneVideo',
            'images' => 'uploadMultipleImage',
            default => throw new \InvalidArgumentException("FormBuilder 上传字段类型无效: {$type}"),
        };
    }

    /**
     * @param array<string, mixed> $attrs
     * @return array<string, mixed>
     */
    private function applyFileAttr(array $attrs, mixed $file): array
    {
        if ($file === false) {
            unset($attrs['data-file']);
            return $attrs;
        }
        if ($file === true || $file === null || $file === '') {
            $attrs['data-file'] = null;
            return $attrs;
        }
        $attrs['data-file'] = strval($file);
        return $attrs;
    }
}
