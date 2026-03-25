<?php

declare(strict_types=1);

namespace think\admin\builder\page\render;

use think\admin\builder\base\render\BuilderAttributes;

/**
 * 页面内容渲染器.
 * @class PageContentRenderer
 */
class PageContentRenderer
{
    public function render(string $notice, string $contentClass, string $content): string
    {
        $html = '<div class="layui-card-body">';
        $html .= "\n\t\t" . '<div class="layui-card-table">';

        if ($notice !== '') {
            $html .= "\n\t\t\t\t" . $notice;
        }

        if ($contentClass !== '') {
            $html .= "\n\t\t\t" . sprintf('<div class="%s">', BuilderAttributes::escape($contentClass));
        }

        if ($content !== '') {
            $html .= "\n\t\t\t\t" . $content;
        }

        if ($contentClass !== '') {
            $html .= "\n\t\t\t" . '</div>';
        }

        $html .= "\n\t\t" . '</div>';
        return $html . "\n\t" . '</div>';
    }
}
