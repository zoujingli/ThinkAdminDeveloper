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
     */
    public function render(array $templates): string
    {
        $html = '';
        $renderer = new PageTemplateScriptRenderer();
        foreach ($templates as $id => $tpl) {
            $html .= $renderer->render((string)$id, $tpl);
        }
        return $html;
    }
}
