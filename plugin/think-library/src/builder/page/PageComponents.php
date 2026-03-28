<?php

declare(strict_types=1);

namespace think\admin\builder\page;

use think\admin\builder\page\component\ButtonGroupComponent;
use think\admin\builder\page\component\CardComponent;
use think\admin\builder\page\component\KeyValueTableComponent;
use think\admin\builder\page\component\KvGridComponent;
use think\admin\builder\page\component\ParagraphsComponent;
use think\admin\builder\page\component\ReadonlyFieldsComponent;

/**
 * 页面组件工厂。
 * @class PageComponents
 */
class PageComponents
{
    public static function card(): CardComponent
    {
        return CardComponent::make();
    }

    /**
     * @param array<int, string> $items
     */
    public static function paragraphs(array $items = []): ParagraphsComponent
    {
        return ParagraphsComponent::make()->items($items);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    public static function kvGrid(array $items = []): KvGridComponent
    {
        return KvGridComponent::make()->items($items);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    public static function buttonGroup(array $items = []): ButtonGroupComponent
    {
        return ButtonGroupComponent::make()->items($items);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    public static function keyValueTable(array $rows = []): KeyValueTableComponent
    {
        return KeyValueTableComponent::make()->rows($rows);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    public static function readonlyFields(array $items = []): ReadonlyFieldsComponent
    {
        return ReadonlyFieldsComponent::make()->items($items);
    }
}
