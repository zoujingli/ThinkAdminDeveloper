<?php

declare(strict_types=1);

namespace think\admin\builder\page;

/**
 * 页面表格配置归一器.
 * @class PageTableNormalizer
 */
class PageTableNormalizer
{
    /**
     * @param array<string, mixed> $attrs
     * @return array<string, mixed>
     */
    public function table(string $tableId, string $tableUrl, array $attrs, bool $hasSearch): array
    {
        $attrs = array_merge([
            'id' => $tableId,
            'data-url' => $tableUrl,
        ], $attrs);
        if ($hasSearch && !isset($attrs['data-target-search'])) {
            $attrs['data-target-search'] = 'form.form-search';
        }
        return $attrs;
    }
}
