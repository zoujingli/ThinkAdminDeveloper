<?php

declare(strict_types=1);

namespace think\admin\builder\form;

use think\admin\builder\BuilderLang;

/**
 * 表单结构通用块。
 * @class FormBlocks
 */
class FormBlocks
{
    public static function fieldset(FormNode $parent, string $title, callable $callback, array $attrs = []): FormNode
    {
        $node = $parent->fieldset();
        $node->class('layui-bg-gray')->attrs($attrs);
        $node->node('legend')->html(sprintf('<b class="layui-badge think-bg-violet">%s</b>', self::escape($title)));
        $callback($node);
        return $node;
    }

    public static function row(FormNode $parent, callable $callback, string $class = 'layui-row layui-col-space15'): FormNode
    {
        $node = $parent->div()->class($class);
        $callback($node);
        return $node;
    }

    public static function col(FormNode $parent, string $class, callable $callback): FormNode
    {
        $node = $parent->div()->class($class);
        $callback($node);
        return $node;
    }

    public static function selectFilter(FormNode $parent, string $name, string $filter, array $groups, string $title, string $subtitle, string $remark): FormNode
    {
        $node = $parent->div()->class('layui-form-item');
        $node->div()->class('help-label')->html(sprintf('<b>%s</b>%s', self::escape($title), self::escape($subtitle)));

        $bar = $node->div()->class('mb10');
        $options = [sprintf('<option value="">%s</option>', self::escape('全部插件'))];
        foreach ($groups as $group) {
            $code = self::escape(strval($group['code'] ?? ''));
            $nameText = self::escape(strval($group['name'] ?? $code));
            $options[] = sprintf('<option value="%s">%s</option>', $code, $nameText);
        }
        $bar->div()->class('layui-input-inline')->html(sprintf(
            '<select name="%s" lay-filter="%s">%s</select>',
            self::escape($name),
            self::escape($filter),
            implode('', $options)
        ));
        $bar->div()->class('layui-form-mid color-desc')->html(self::escape($remark));
        return $node;
    }

    public static function groupedTemplateChoices(
        FormNode $parent,
        array $groups,
        string $type,
        string $name,
        string $groupClass,
        string $dataAttribute,
        string $selectedField
    ): FormNode {
        $node = $parent->div()->class('layui-textarea help-checks');

        foreach ($groups as $group) {
            $groupNode = $node->div()->class($groupClass)->data($dataAttribute, strval($group['code'] ?? ''));
            $groupNode->div()->class('pl5 mb5')->html(sprintf(
                '<span class="layui-badge layui-bg-blue">%s</span>',
                self::escape(strval($group['name'] ?? ''))
            ));

            foreach ((array)($group['items'] ?? []) as $item) {
                $value = strval($item['code'] ?? $item['id'] ?? '');
                $label = strval($item['name'] ?? $item['title'] ?? $value);
                $pluginText = trim(strval($item['plugin_text'] ?? ''));
                $extra = '';
                if ($pluginText !== '' && intval($item['plugin_count'] ?? 0) > 1) {
                    $extra = sprintf('<span class="color-desc">(%s)</span>', self::escape($pluginText));
                }

                if ($type === 'radio') {
                    $checked = sprintf('{if isset($vo.%s) and $vo.%s eq \'%s\'} checked{/if}', $selectedField, $selectedField, addslashes($value));
                    $inputName = $name;
                } else {
                    $checked = sprintf('{if in_array(\'%s\', $vo.%s)} checked{/if}', addslashes($value), $selectedField);
                    $inputName = $name . '[]';
                }

                $groupNode->html(sprintf(
                    '<label class="think-checkbox"><input type="%s" name="%s" value="%s" lay-ignore%s>%s%s</label>',
                    self::escape($type),
                    self::escape($inputName),
                    self::escape($value),
                    $checked,
                    self::escape($label),
                    $extra === '' ? '' : (' ' . $extra)
                ));
            }
        }

        return $node;
    }

    private static function escape(string $content): string
    {
        return htmlentities(BuilderLang::text($content), ENT_QUOTES, 'UTF-8');
    }
}
