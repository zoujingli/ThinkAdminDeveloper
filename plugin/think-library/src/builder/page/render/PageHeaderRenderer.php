<?php

declare(strict_types=1);

namespace think\admin\builder\page\render;

use think\admin\builder\BuilderLang;
use think\admin\builder\base\render\BuilderAttributes;

/**
 * 页面头部渲染器.
 * @class PageHeaderRenderer
 */
class PageHeaderRenderer
{
    /**
     * @param array<int, string> $buttons
     */
    public function render(string $title, array $buttons): string
    {
        if ($title === '' && count($buttons) < 1) {
            return '';
        }

        $html = '<div class="layui-card-header">';
        if ($title !== '') {
            $html .= sprintf('<span class="layui-icon font-s10 color-desc mr5">&#xe65b;</span>%s', BuilderAttributes::escape(BuilderLang::text($title)));
        }
        if (count($buttons) > 0) {
            $html .= sprintf('<div class="pull-right">%s</div>', join("\n", $buttons));
        }
        return $html . '</div>';
    }
}
