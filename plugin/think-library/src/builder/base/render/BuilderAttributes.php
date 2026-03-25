<?php

declare(strict_types=1);

namespace think\admin\builder\base\render;

/**
 * Builder 属性对象.
 * @class BuilderAttributes
 */
class BuilderAttributes
{
    /**
     * @param array<string, mixed> $attrs
     */
    public function __construct(private array $attrs = [])
    {
    }

    /**
     * @param array<string, mixed> $attrs
     */
    public static function make(array $attrs = []): self
    {
        return new self($attrs);
    }

    /**
     * @param array<string, mixed> $attrs
     */
    public function merge(array $attrs): self
    {
        foreach ($attrs as $name => $value) {
            if ($name === 'class') {
                $this->class(is_array($value) ? $value : strval($value));
            } else {
                $this->attrs[$name] = $value;
            }
        }
        return $this;
    }

    public function class(string|array $class): self
    {
        $merged = self::mergeClassNames(strval($this->attrs['class'] ?? ''), $class);
        if ($merged === '') {
            unset($this->attrs['class']);
        } else {
            $this->attrs['class'] = $merged;
        }
        return $this;
    }

    public function default(string $name, mixed $value): self
    {
        if (!array_key_exists($name, $this->attrs)) {
            $this->attrs[$name] = $value;
        }
        return $this;
    }

    /**
     * @param array<int, array<string, mixed>> $modules
     */
    public function modules(array $modules): self
    {
        if (count($modules) > 0) {
            $this->attrs['data-builder-modules'] = json_encode($modules, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $attrs = $this->attrs;
        if (isset($attrs['class']) && trim(strval($attrs['class'])) === '') {
            unset($attrs['class']);
        }
        return $attrs;
    }

    public function html(): string
    {
        $html = '';
        foreach ($this->all() as $key => $value) {
            $name = self::escape((string)$key);
            $html .= is_null($value)
                ? sprintf(' %s', $name)
                : sprintf(' %s="%s"', $name, self::escape((string)$value));
        }
        return ltrim($html);
    }

    public static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    public static function mergeClassNames(string|array $origin, string|array $append): string
    {
        $classes = [];
        foreach ([self::normalizeClasses($origin), self::normalizeClasses($append)] as $items) {
            foreach ($items as $class) {
                if ($class !== '' && !in_array($class, $classes, true)) {
                    $classes[] = $class;
                }
            }
        }
        return join(' ', $classes);
    }

    /**
     * @return array<int, string>
     */
    private static function normalizeClasses(string|array $classes): array
    {
        if (is_array($classes)) {
            $items = array_map('strval', $classes);
        } else {
            $items = preg_split('/\s+/', trim($classes)) ?: [];
        }

        $result = [];
        foreach ($items as $class) {
            $class = trim($class);
            if ($class !== '') {
                $result[] = $class;
            }
        }
        return $result;
    }
}
