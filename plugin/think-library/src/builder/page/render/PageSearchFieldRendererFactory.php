<?php

declare(strict_types=1);

namespace think\admin\builder\page\render;

/**
 * 页面搜索字段渲染器工厂.
 * @class PageSearchFieldRendererFactory
 */
class PageSearchFieldRendererFactory
{
    /**
     * @param array<string, mixed> $field
     */
    public function create(array $field): PageSearchFieldRendererInterface
    {
        return match (strtolower(strval($field['type'] ?? 'input'))) {
            'hidden' => new PageSearchHiddenFieldRenderer(),
            'select' => new PageSearchSelectFieldRenderer(),
            'submit' => new PageSearchSubmitFieldRenderer(),
            default => new PageSearchInputFieldRenderer(),
        };
    }
}
