<?php

declare(strict_types=1);

namespace think\admin\builder\page\render;

/**
 * 页面模板片段渲染器.
 * @class PageTemplateRenderer
 */
class PageTemplateRenderer
{
    /**
     * @param array<string, string> $templates
     * @param array<int, string> $rowActions
     */
    public function render(array $templates, string $toolbarId, array $rowActions): string
    {
        $templates = (new PageToolbarTemplateRenderer())->render($templates, $toolbarId, $rowActions);

        $html = '';
        $renderer = new PageTemplateScriptRenderer();
        foreach ($templates as $id => $tpl) {
            $html .= $renderer->render((string)$id, $tpl);
        }
        return $html;
    }
}
