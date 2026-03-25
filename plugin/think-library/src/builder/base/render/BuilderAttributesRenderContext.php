<?php

declare(strict_types=1);

namespace think\admin\builder\base\render;

/**
 * Builder 属性渲染上下文.
 * @class BuilderAttributesRenderContext
 */
abstract class BuilderAttributesRenderContext
{
    /**
     * @param callable(array<string, mixed>): string $attrsRenderer
     */
    public function __construct(
        private $attrsRenderer,
    ) {
    }

    /**
     * @param array<string, mixed> $attrs
     */
    public function attrs(array $attrs): string
    {
        return ($this->attrsRenderer)($attrs);
    }

    public function escape(string $value): string
    {
        return BuilderAttributes::escape($value);
    }

    public function mergeClass(string|array $origin, string|array $append): string
    {
        return BuilderAttributes::mergeClassNames($origin, $append);
    }
}
