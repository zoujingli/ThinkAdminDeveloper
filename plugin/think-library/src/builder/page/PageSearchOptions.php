<?php

declare(strict_types=1);

namespace think\admin\builder\page;

use think\admin\builder\base\BuilderOptionSource;

/**
 * 页面搜索字段选项源对象.
 * @class PageSearchOptions
 */
class PageSearchOptions extends BuilderOptionSource
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(PageSearchField $owner, array $options = [], string $source = '')
    {
        parent::__construct('source', $options, $source, $owner);
    }
}
