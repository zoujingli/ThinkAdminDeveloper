<?php

declare(strict_types=1);

namespace think\admin\builder\base\render;

/**
 * 通用 JSON Script 渲染器.
 * @class JsonScriptRenderer
 */
class JsonScriptRenderer
{
    /**
     * @param array<string, mixed> $payload
     */
    public function render(array $payload, string $className): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        return $json ? sprintf('<script type="application/json" class="%s">%s</script>', BuilderAttributes::escape($className), $json) : '';
    }
}
