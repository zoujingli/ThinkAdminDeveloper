<?php

declare(strict_types=1);

namespace think\admin\builder\page;

/**
 * 页面结构通用块。
 * @class PageBlocks
 */
class PageBlocks
{
    public static function section(PageNode $parent, ?string $class = null, ?callable $callback = null): PageNode
    {
        $node = $parent->section();
        if ($class !== null && $class !== '') {
            $node->class($class);
        }
        if (is_callable($callback)) {
            $callback($node);
        }
        return $node;
    }

    public static function card(PageNode $parent, ?string $title = null, ?callable $callback = null, string $class = 'layui-card'): PageNode
    {
        $card = $parent->div()->class($class);
        if ($title !== null && $title !== '') {
            $card->div()->class('layui-card-header notselect')->html(sprintf(
                '<span class="help-label"><b>%s</b></span>',
                self::escape($title)
            ));
        }
        $body = $card->div()->class('layui-card-body');
        if (is_callable($callback)) {
            $callback($body);
        }
        return $card;
    }

    public static function grid(PageNode $parent, string $class, callable $callback): PageNode
    {
        $node = $parent->div()->class($class);
        $callback($node);
        return $node;
    }

    public static function column(PageNode $parent, string $class, callable $callback): PageNode
    {
        $node = $parent->div()->class($class);
        $callback($node);
        return $node;
    }

    public static function title(PageNode $parent, string $title, string $tag = 'h3', string $class = ''): PageNode
    {
        $node = $parent->node($tag);
        if ($class !== '') {
            $node->class($class);
        }
        $node->html(self::escape($title));
        return $node;
    }

    public static function text(PageNode $parent, string $text, string $class = ''): PageNode
    {
        $node = $parent->div();
        if ($class !== '') {
            $node->class($class);
        }
        $node->html(self::escape($text));
        return $node;
    }

    public static function stat(PageNode $parent, string $label, string $value, string $class = ''): PageNode
    {
        $node = $parent->div()->class(trim($class) === '' ? 'ta-stat-item' : $class);
        $node->html(sprintf('<span>%s</span><strong>%s</strong>', self::escape($label), self::escape($value)));
        return $node;
    }

    public static function kv(PageNode $parent, string $label, string $value, string $class = ''): PageNode
    {
        $node = $parent->div()->class(trim($class) === '' ? 'ta-kv-item' : $class);
        $node->html(sprintf(
            '<span class="ta-kv-label">%s</span><span class="ta-kv-value">%s</span>',
            self::escape($label),
            self::escape($value)
        ));
        return $node;
    }

    private static function escape(string $content): string
    {
        return htmlentities($content, ENT_QUOTES, 'UTF-8');
    }
}
