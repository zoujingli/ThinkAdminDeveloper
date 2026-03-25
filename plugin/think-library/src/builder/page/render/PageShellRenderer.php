<?php

declare(strict_types=1);

namespace think\admin\builder\page\render;

/**
 * 页面根容器渲染器.
 * @class PageShellRenderer
 */
class PageShellRenderer
{
    /**
     * @param array<int, string> $buttons
     */
    public function render(
        string $title,
        array $buttons,
        string $message,
        string $contentClass,
        string $content,
        string $templates,
        string $scripts,
        string $schemaScript
    ): string {
        $header = (new PageHeaderRenderer())->render($title, $buttons);
        $notice = (new PageNoticeRenderer())->render($message);
        $contentHtml = (new PageContentRenderer())->render($notice, $contentClass, $content);

        $html = '<div class="layui-card" data-builder-scope="page">';
        if ($header !== '') {
            $html .= "\n\t" . $header;
        }

        $html .= "\n\t" . '<div class="layui-card-line"></div>';
        $html .= "\n\t" . $contentHtml;
        $html .= $templates;
        $html .= $scripts;
        $html .= "\n\t" . $schemaScript;
        return $html . "\n</div>";
    }
}
