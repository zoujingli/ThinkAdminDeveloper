<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdmin
 * +----------------------------------------------------------------------
 * | 版权所有 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | 官方网站: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | 开源协议 ( https://mit-license.org )
 * | 免责声明 ( https://thinkadmin.top/disclaimer )
 * | 会员特权 ( https://thinkadmin.top/vip-introduce )
 * +----------------------------------------------------------------------
 * | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
 * | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

namespace think\admin\service;

use Exception;
use ReflectionClass;
use ReflectionMethod;
use ReflectionType;
use think\App;
use think\Container;
use think\exception\HttpResponseException;

/**
 * 标准 JSON-RPC 服务端工具。
 */
class JsonRpcHttpServer
{
    /**
     * 当前 App 对象。
     */
    protected App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * 静态实例对象。
     */
    public static function instance(...$args): static
    {
        return Container::getInstance()->make(static::class, $args);
    }

    /**
     * 设置监听对象。
     *
     * @param mixed $object
     */
    public function handle($object): void
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
     *
     * @param mixed $object
     */
    protected function printMethod($object): void
    {
        try {
            $object = new ReflectionClass($object);
            echo "<h2>{$object->getName()}</h2><hr>";
            foreach ($object->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if (stripos($method->getName(), '_') === 0) {
                    continue;
                }
                $params = [];
                foreach ($method->getParameters() as $parameter) {
                    $type = $parameter->getType();
                    if ($type instanceof ReflectionType) {
                        $type = $type->getName();
                    }
                    $params[] = ($type ? "{$type} $" : '$') . $parameter->getName();
                }
                $params = count($params) > 0 ? join(', ', $params) : '';
                echo '<div style="color:#666">' . nl2br($method->getDocComment() ?: '') . '</div>';
                echo "<div style='color:#00E'>{$object->getShortName()}::{$method->getName()}({$params})</div><br>";
            }
        } catch (Exception $exception) {
            echo "<h3>[{$exception->getCode()}] {$exception->getMessage()}</h3>";
        }
    }

    /**
     * 构建错误响应。
     *
     * @param mixed $result
     * @param mixed $id
     */
    private function errorResponse($id, string $code, string $message, string $meaning, $result = null): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result,
            'error' => ['code' => $code, 'message' => $message, 'meaning' => $meaning],
        ];
    }

    /**
     * 执行 RPC 方法调用。
     * @param mixed $object
     */
    protected function dispatchRequest($object, array $request): array
    {
        try {
            if ($object instanceof Exception) {
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
        } catch (Exception $exception) {
            return $this->errorResponse($request['id'], (string)$exception->getCode(), lang($exception->getMessage()), lang('System Exception.'));
        }
    }

    /**
     * 构建成功响应。
     * @param mixed $id
     * @param mixed $result
     */
    private function successResponse($id, $result): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result, 'error' => null];
    }
}
