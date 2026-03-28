<?php

declare(strict_types=1);

namespace think\admin\builder;

/**
 * Builder 文案翻译辅助类。
 * @class BuilderLang
 */
class BuilderLang
{
    /**
     * @var array<int, string>
     */
    private const TRANSLATABLE_ATTRS = [
        'placeholder',
        'title',
        'data-title',
        'data-confirm',
        'data-tips-text',
        'required-error',
        'pattern-error',
        'lay-text',
    ];

    public static function text(string $text): string
    {
        if ($text === '' || self::looksLikeHtml($text) || !function_exists('lang')) {
            return $text;
        }

        try {
            return strval(lang($text));
        } catch (\Throwable) {
            return $text;
        }
    }

    /**
     * @param array<int, mixed> $vars
     */
    public static function format(string $text, array $vars = []): string
    {
        if ($text === '') {
            return '';
        }
        if (!function_exists('lang')) {
            return self::fallbackFormat($text, $vars);
        }

        try {
            return strval(lang($text, $vars));
        } catch (\Throwable) {
            return self::fallbackFormat($text, $vars);
        }
    }

    public static function pipeText(string $text, string $separator = '|'): string
    {
        if ($text === '' || !str_contains($text, $separator)) {
            return self::text($text);
        }

        return join($separator, array_map(
            static fn(string $item): string => self::text(trim($item)),
            explode($separator, $text)
        ));
    }

    /**
     * @param array<string, mixed> $attrs
     * @return array<string, mixed>
     */
    public static function attrs(array $attrs): array
    {
        foreach ($attrs as $name => $value) {
            if (!is_string($name) || !is_string($value) || !in_array($name, self::TRANSLATABLE_ATTRS, true)) {
                continue;
            }
            $attrs[$name] = $name === 'lay-text' ? self::pipeText($value) : self::text($value);
        }

        return $attrs;
    }

    /**
     * @param array<mixed> $options
     * @return array<mixed>
     */
    public static function options(array $options): array
    {
        $translated = [];
        foreach ($options as $value => $label) {
            if (is_string($label)) {
                $translated[$value] = self::text($label);
                continue;
            }
            if (is_array($label)) {
                $translated[$value] = self::options($label);
                continue;
            }
            $translated[$value] = $label;
        }

        return $translated;
    }

    private static function looksLikeHtml(string $text): bool
    {
        return str_contains($text, '<') && str_contains($text, '>');
    }

    /**
     * @param array<int, mixed> $vars
     */
    private static function fallbackFormat(string $text, array $vars): string
    {
        if (count($vars) < 1) {
            return $text;
        }

        try {
            return vsprintf($text, $vars);
        } catch (\Throwable) {
            return $text;
        }
    }
}
