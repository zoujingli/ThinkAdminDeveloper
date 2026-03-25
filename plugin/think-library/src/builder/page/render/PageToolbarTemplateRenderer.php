<?php

declare(strict_types=1);

namespace think\admin\builder\page\render;

/**
 * 页面工具栏模板渲染器.
 * @class PageToolbarTemplateRenderer
 */
class PageToolbarTemplateRenderer
{
    /**
     * @param array<string, string> $templates
     * @param array<int, string> $rowActions
     * @return array<string, string>
     */
    public function render(array $templates, string $toolbarId, array $rowActions): array
    {
        if (count($rowActions) > 0 && !isset($templates[$toolbarId])) {
            $templates[$toolbarId] = join("\n", $rowActions);
        }
        return $templates;
    }
}
