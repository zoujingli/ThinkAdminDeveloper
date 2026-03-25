<?php

declare(strict_types=1);

namespace think\admin\builder\page\render;

use think\admin\builder\base\render\BuilderAttributesRenderContext;

/**
 * 页面搜索渲染上下文.
 * @class PageSearchRenderContext
 */
class PageSearchRenderContext
extends BuilderAttributesRenderContext
{
    /**
     * @param callable(array<string, mixed>): string $attrsRenderer
     * @param callable(string): string $searchValueResolver
     * @param callable(array<string, mixed>): array $optionsResolver
     */
    public function __construct(
        callable $attrsRenderer,
        private $searchValueResolver,
        private $optionsResolver,
    ) {
        parent::__construct($attrsRenderer);
    }

    public function searchValue(string $name): string
    {
        return ($this->searchValueResolver)($name);
    }

    /**
     * @param array<string, mixed> $field
     * @return array<mixed>
     */
    public function resolveOptions(array $field): array
    {
        return ($this->optionsResolver)($field);
    }

}
