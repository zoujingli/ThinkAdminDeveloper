<?php

declare(strict_types=1);

namespace think\admin\builder\page\component;

use think\admin\builder\page\PageNode;

/**
 * 页面组件接口。
 * @class PageComponentInterface
 */
interface PageComponentInterface
{
    public function mount(PageNode $parent): PageNode;
}
