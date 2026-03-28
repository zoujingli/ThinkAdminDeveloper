<?php

declare(strict_types=1);

namespace plugin\system\builder;

use think\admin\builder\BuilderLang;

/**
 * 系统列表页 Tabs 渲染辅助。
 * @class SystemListTabs
 */
class SystemListTabs
{
    /**
     * @param array<int, array<string, mixed>> $items
     */
    public static function render(array $items): string
    {
        $html = '<ul class="layui-tab-title">';
        foreach ($items as $item) {
            $class = !empty($item['active']) ? ' class="layui-this"' : '';
            $html .= sprintf(
                '<li data-open="%s"%s>%s</li>',
                htmlspecialchars(strval($item['url'] ?? ''), ENT_QUOTES, 'UTF-8'),
                $class,
                htmlspecialchars(BuilderLang::text(strval($item['label'] ?? '')), ENT_QUOTES, 'UTF-8')
            );
        }
        return $html . '</ul>';
    }

    public static function single(string $label): string
    {
        return self::render([['label' => $label, 'url' => 'javascript:void(0);', 'active' => true]]);
    }

    public static function indexRecycle(string $type, string $indexUrl, string $indexLabel): string
    {
        return self::render([
            ['label' => $indexLabel, 'url' => $indexUrl . '?type=index', 'active' => $type === 'index'],
            ['label' => lang('回 收 站'), 'url' => $indexUrl . '?type=recycle', 'active' => $type === 'recycle'],
        ]);
    }

    /**
     * @param array<string, scalar> $indexParams
     * @param array<string, scalar> $recycleParams
     */
    public static function indexRecycleByParams(string $type, string $indexLabel, array $indexParams = [], array $recycleParams = []): string
    {
        return self::render([
            ['label' => $indexLabel, 'url' => url('index', $indexParams + ['type' => 'index'])->build(), 'active' => $type === 'index'],
            ['label' => lang('回 收 站'), 'url' => url('index', $recycleParams + ['type' => 'recycle'])->build(), 'active' => $type === 'recycle'],
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $roots
     */
    public static function menu(string $indexUrl, string $type, string $pid, array $roots): string
    {
        $items = [
            ['label' => lang('全部菜单'), 'url' => $indexUrl . '?type=index', 'active' => $type === 'index' && $pid === ''],
            ['label' => lang('回收站'), 'url' => $indexUrl . '?type=recycle', 'active' => $type === 'recycle' && $pid === ''],
        ];
        foreach ($roots as $root) {
            $id = intval($root['id'] ?? 0);
            if ($id < 1) {
                continue;
            }
            $items[] = [
                'label' => strval($root['title'] ?? '-'),
                'url' => $indexUrl . '?type=index&pid=' . $id,
                'active' => $type === 'index' && $pid !== '' && $pid === strval($id),
            ];
        }
        return self::render($items);
    }
}
