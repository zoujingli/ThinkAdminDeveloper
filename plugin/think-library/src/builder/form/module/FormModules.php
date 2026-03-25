<?php

declare(strict_types=1);

namespace think\admin\builder\form\module;

use think\admin\builder\form\FormNode;

/**
 * 表单通用模块。
 * @class FormModules
 */
class FormModules
{
    /**
     * @param array<string, mixed> $config
     */
    public static function intro(FormNode $parent, array $config = []): FormNode
    {
        $node = $parent->section()->class(trim(strval($config['class'] ?? 'layui-card mb15')));
        $body = $node->div()->class(trim(strval($config['body_class'] ?? 'layui-card-body')));
        $eyebrow = trim(strval($config['eyebrow'] ?? ''));
        if ($eyebrow !== '') {
            $body->div()->class(trim(strval($config['eyebrow_class'] ?? 'color-desc fs12')))->html(self::escape($eyebrow));
        }
        $title = trim(strval($config['title'] ?? ''));
        if ($title !== '') {
            $body->node('h2')->class(trim(strval($config['title_class'] ?? 'mt10 mb10')))->html(self::escape($title));
        }
        $description = trim(strval($config['description'] ?? ''));
        if ($description !== '') {
            $body->div()->class(trim(strval($config['description_class'] ?? 'color-desc lh24')))->html(self::escape($description));
        }
        return $node;
    }

    /**
     * @param array<string, mixed> $config
     * @param callable(FormNode): void $callback
     */
    public static function section(FormNode $parent, array $config, callable $callback): FormNode
    {
        $node = $parent->section()->class(trim(strval($config['class'] ?? 'mb20')));
        $head = $node->div()->class(trim(strval($config['head_class'] ?? 'mb15')));
        $title = trim(strval($config['title'] ?? ''));
        if ($title !== '') {
            $head->node('h3')->class(trim(strval($config['title_class'] ?? 'mb5')))->html(self::escape($title));
        }
        $description = trim(strval($config['description'] ?? ''));
        if ($description !== '') {
            $head->node('p')->class(trim(strval($config['description_class'] ?? 'color-desc lh24')))->html(self::escape($description));
        }
        $body = $node->div()->class(trim(strval($config['body_class'] ?? '')));
        $callback($body);
        return $node;
    }

    public static function note(FormNode $parent, string $text, string $class = 'help-block color-desc'): FormNode
    {
        return $parent->div()->class($class)->html(self::escape($text));
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function readonlyField(FormNode $parent, array $config = []): FormNode
    {
        $node = $parent->node('label')->class(trim(strval($config['class'] ?? 'relative block')));
        $label = $node->node('span')->class(trim(strval($config['label_class'] ?? 'help-label label-required-prev')));
        $title = trim(strval($config['title'] ?? ''));
        if ($title !== '') {
            $label->node('b')->html(self::escape($title));
        }
        $subtitle = trim(strval($config['subtitle'] ?? ''));
        if ($subtitle !== '') {
            $label->html(($title === '' ? '' : '') . self::escape($title === '' ? $subtitle : " {$subtitle}"));
        }
        $wrap = $node->node('label')->class(trim(strval($config['wrap_class'] ?? 'relative block')));
        $inputAttrs = [
            'readonly' => null,
            'class' => trim(strval($config['input_class'] ?? 'layui-input layui-bg-gray')),
            'value' => strval($config['value'] ?? ''),
        ];
        $inputName = trim(strval($config['name'] ?? ''));
        if ($inputName !== '') {
            $inputAttrs['name'] = $inputName;
        }
        $input = $wrap->node('input')->attrs($inputAttrs);
        foreach ((array)($config['input_attrs'] ?? []) as $name => $value) {
            $input->attr(strval($name), $value);
        }
        $copy = trim(strval($config['copy'] ?? ''));
        if ($copy !== '') {
            $wrap->node('a')->class(trim(strval($config['copy_class'] ?? 'layui-icon layui-icon-release input-right-icon')))->attr('data-copy', $copy);
        }
        $help = trim(strval($config['help'] ?? ''));
        if ($help !== '') {
            self::note($node, $help);
        }
        return $node;
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function pickerField(FormNode $parent, array $config = []): FormNode
    {
        $node = $parent->node('label')->class(trim(strval($config['class'] ?? 'relative block')));
        foreach ((array)($config['attrs'] ?? []) as $name => $value) {
            $node->attr(strval($name), $value);
        }
        $label = $node->node('span')->class(trim(strval($config['label_class'] ?? 'help-label label-required-prev')));
        $title = trim(strval($config['title'] ?? ''));
        if ($title !== '') {
            $label->node('b')->html(self::escape($title));
        }
        $subtitle = trim(strval($config['subtitle'] ?? ''));
        if ($subtitle !== '') {
            $label->html(($title === '' ? '' : '') . self::escape($title === '' ? $subtitle : " {$subtitle}"));
        }

        $hiddenName = trim(strval($config['hidden_name'] ?? ''));
        if ($hiddenName !== '') {
            $node->node('input')->attrs([
                'type' => 'hidden',
                'name' => $hiddenName,
                'value' => strval($config['hidden_value'] ?? ''),
            ]);
        }

        $input = $node->node('input')->attrs([
            'readonly' => null,
            'class' => trim(strval($config['input_class'] ?? 'layui-input')),
            'value' => strval($config['value'] ?? ''),
            'title' => strval($config['value'] ?? ''),
        ]);
        $inputName = trim(strval($config['name'] ?? ''));
        if ($inputName !== '') {
            $input->attr('name', $inputName);
        }
        foreach ((array)($config['input_attrs'] ?? []) as $name => $value) {
            $input->attr(strval($name), $value);
        }
        $icon = $node->node('span')->class(trim(strval($config['icon_class'] ?? 'input-right-icon layui-icon layui-icon-theme')));
        foreach ((array)($config['icon_attrs'] ?? []) as $name => $value) {
            $icon->attr(strval($name), $value);
        }
        $help = trim(strval($config['help'] ?? ''));
        if ($help !== '') {
            self::note($node, $help);
        }
        return $node;
    }

    private static function escape(string $content): string
    {
        return htmlentities($content, ENT_QUOTES, 'UTF-8');
    }
}
