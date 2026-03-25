<?php

declare(strict_types=1);

namespace think\admin\builder\base\render;

/**
 * Builder 属性 HTML 渲染器.
 * @class BuilderAttributesRenderer
 */
class BuilderAttributesRenderer
{
    /**
     * @param array<string, mixed> $attrs
     */
    public function render(array $attrs): string
    {
        return BuilderAttributes::make($attrs)->html();
    }
}
