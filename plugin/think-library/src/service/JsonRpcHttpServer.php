<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdminDeveloper
 * +----------------------------------------------------------------------
 * | Copyright (c) 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | Official Website: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | Licensed: https://mit-license.org
 * | Disclaimer: https://thinkadmin.top/disclaimer
 * | Vip Rights: https://thinkadmin.top/vip-introduce
 * +----------------------------------------------------------------------
 * | Gitee Repository: https://gitee.com/zoujingli/ThinkAdmin
 * | Github Repository: https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

namespace think\admin\service;

use think\App;
use think\Container;
use think\exception\HttpResponseException;

/**
 * 标准 JSON-RPC 服务端工具。
 */
final class JsonRpcHttpServer
{
    /**
     * 当前 App 对象。
     */
    private App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * 静态实例对象。
     */
    public static function instance(...$args): JsonRpcHttpServer
    {
        return Container::getInstance()->make(self::class, $args);
    }

    /**
     * 设置监听对象。
     */
    public function handle(mixed $object): void
    {
        if ($this->app->request->method() !== 'POST' || $this->app->request->contentType() !== 'application/json') {
            $this->printMethod($object);
            return;
        }
        $request = json_decode(file_get_contents('php://input'), true) ?: [];
        if (empty($request)) {
            $response = $this->errorResponse('0', '-32700', lang('Syntax parsing error.'), lang('Invalid JSON parameter.'));
        } elseif (!isset($request['id'], $request['method'], $request['params'])) {
            $response = $this->errorResponse($request['id'] ?? '0', '-32600', lang('Invalid request.'), lang('Invalid JSON parameter.'));
        } else {
            $response = $this->dispatchRequest($object, $request);
        }
        throw new HttpResponseException(json($response));
    }

    /**
     * 打印输出对象方法。
     */
    private function printMethod(mixed $object): void
    {
        try {
            $object = new \ReflectionClass($object);
            echo "<h2>{$object->getName()}</h2><hr>";
            foreach ($object->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if (stripos($method->getName(), '_') === 0) {
                    continue;
                }
                $params = [];
                foreach ($method->getParameters() as $parameter) {
                    $type = $this->normalizeReflectionType($parameter->getType());
                    $params[] = ($type ? "{$type} $" : '$') . $parameter->getName();
                }
                $params = count($params) > 0 ? join(', ', $params) : '';
                echo '<div style="color:#666">' . nl2br($method->getDocComment() ?: '') . '</div>';
                echo "<div style='color:#00E'>{$object->getShortName()}::{$method->getName()}({$params})</div><br>";
            }
        } catch (\Exception $exception) {
            echo "<h3>[{$exception->getCode()}] {$exception->getMessage()}</h3>";
        }
    }

    /**
     * 执行 RPC 方法调用。
     */
    private function dispatchRequest(mixed $object, array $request): array
    {
        try {
            if ($object instanceof \Exception) {
                throw $object;
            }
            if (strtolower($request['method']) === '_get_class_name_') {
                return $this->successResponse($request['id'], get_class($object));
            }
            if (!method_exists($object, $request['method'])) {
                $info = lang('method not exists: %s::%s', [class_basename($object), $request['method']]);
                return $this->errorResponse($request['id'], '-32601', $info, lang('The method does not exist or is invalid.'));
            }
            $result = call_user_func_array([$object, $request['method']], $request['params']);
            return $this->successResponse($request['id'], $result);
        } catch (\think\admin\Exception $exception) {
            return $this->errorResponse($request['id'], (string)$exception->getCode(), lang($exception->getMessage()), lang('Business Exception.'), $exception->getData());
        } catch (\Exception $exception) {
            return $this->errorResponse($request['id'], (string)$exception->getCode(), lang($exception->getMessage()), lang('System Exception.'));
        }
    }

    /**
     * 构建错误响应。
     */
    private function errorResponse(mixed $id, string $code, string $message, string $meaning, mixed $result = null): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result,
            'error' => ['code' => $code, 'message' => $message, 'meaning' => $meaning],
        ];
    }

    /**
     * 构建成功响应。
     */
    private function successResponse(mixed $id, mixed $result): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result, 'error' => null];
    }

    /**
     * 标准化反射类型名称，兼容联合类型与交叉类型。
     */
    private function normalizeReflectionType(?\ReflectionType $type): string
    {
        if ($type instanceof \ReflectionNamedType) {
            return $type->getName();
        }
        if ($type instanceof \ReflectionUnionType) {
            return implode('|', array_map(fn (\ReflectionNamedType $item) => $item->getName(), $type->getTypes()));
        }
        if ($type instanceof \ReflectionIntersectionType) {
            return implode('&', array_map(fn (\ReflectionNamedType $item) => $item->getName(), $type->getTypes()));
        }
        return '';
    }
}
