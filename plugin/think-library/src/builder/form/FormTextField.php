<?php

declare(strict_types=1);

namespace think\admin\builder\form;

use think\admin\builder\base\render\BuilderAttributes;
use think\admin\builder\base\render\BuilderAttributesRenderer;

/**
 * 文本类字段节点.
 * @class FormTextField
 */
class FormTextField extends FormField
{
    public function maxlength(int $length): static
    {
        return $this->setFieldAttr('maxlength', $length);
    }

    public function minlength(int $length): static
    {
        return $this->setFieldAttr('minlength', $length);
    }

    /**
     * @param array<string, mixed> $attrs
     */
    public function inputRightIcon(string $iconClass, array $attrs = [], string|array $inputClass = 'pr40'): static
    {
        $iconClass = trim($iconClass);
        if ($iconClass === '') {
            return $this;
        }

        $renderer = new BuilderAttributesRenderer();
        $attrs['onmousedown'] = $this->mergeEventHandler(strval($attrs['onmousedown'] ?? ''), 'event.preventDefault();event.stopPropagation();');
        $attrs['ontouchstart'] = $this->mergeEventHandler(strval($attrs['ontouchstart'] ?? ''), 'event.preventDefault();event.stopPropagation();');
        $attrs = BuilderAttributes::make($attrs)
            ->class(trim("input-right-icon layui-icon {$iconClass}"))
            ->all();

        $this->body()->class('relative');
        $this->input()->class($inputClass)->html(sprintf('<a %s></a>', $renderer->render($attrs)));
        return $this;
    }

    private function mergeEventHandler(string $origin, string $append): string
    {
        $origin = trim($origin);
        $append = trim($append);
        if ($origin === '') {
            return $append;
        }
        if ($append === '') {
            return $origin;
        }
        return rtrim($append, ';') . ';' . ltrim($origin, ';');
    }
}
