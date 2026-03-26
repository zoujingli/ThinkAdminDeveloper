<?php

declare(strict_types=1);

namespace plugin\system\builder;

use think\admin\builder\page\PageLayout;

/**
 * 系统列表页公共样式预设。
 * @class SystemListPage
 */
class SystemListPage
{
    public static function apply(PageLayout $page, string $title, string $requestBaseUrl = ''): PageLayout
    {
        $page->title($title)
            ->contentClass('')
            ->showSearchLegend(false);
        if ($requestBaseUrl !== '') {
            $page->searchAttrs(['action' => $requestBaseUrl]);
        }
        return $page;
    }
}
