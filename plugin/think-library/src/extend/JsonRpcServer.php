<?php

// +----------------------------------------------------------------------
// | Library for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// | 免费声明 ( https://thinkadmin.top/disclaimer )
// +----------------------------------------------------------------------
// | gitee 仓库地址 ：https://gitee.com/zoujingli/ThinkLibrary
// | github 仓库地址 ：https://github.com/zoujingli/ThinkLibrary
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace think\admin\extend;

use think\App;
use think\Container;
use think\exception\HttpResponseException;

/**
 * JsonRpc 服务端
 * @class JsonRpcServer
 * @package think\admin\extend
 */
class JsonRpcServer
{
    /**
     * 当前App对象
     * @var App
     */
    protected $app;

    /**
     * JsonRpcServer constructor.
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * 静态实例对象
     * @param array $args
     * @return static
     */
    public static function instance(...$args): JsonRpcServer
    {
        return Container::getInstance()->make(static::class, $args);
    }

    /**
     * 设置监听对象
     * @param mixed $object
     */
    public function handle($object)
    {
        // Checks if a JSON-RCP request has been received
        if ($this->app->request->method() !== 'POST' || $this->app->request->contentType() !== 'application/json') {
            $this->printMethod($object);
        } else {
            // Reads the input data
            $request = json_decode(file_get_contents('php://input'), true) ?: [];
            if (empty($request)) {
                $error = ['code' => '-32700', 'message' => lang('Syntax parsing error.'), 'meaning' => lang('Invalid JSON parameter.')];
                $response = ['jsonrpc' => '2.0', 'id' => '0', 'result' => null, 'error' => $error];
            } elseif (!isset($request['id']) || !isset($request['method']) || !isset($request['params'])) {
                $error = ['code' => '-32600', 'message' => lang('Invalid request.'), 'meaning' => lang('Invalid JSON parameter.')];
                $response = ['jsonrpc' => '2.0', 'id' => $request['id'] ?? '0', 'result' => null, 'error' => $error];
            } else try {
                if ($object instanceof \Exception) {
                    throw $object;
                } elseif (strtolower($request['method']) === '_get_class_name_') {
                    $response = ['jsonrpc' => '2.0', 'id' => $request['id'], 'result' => get_class($object), 'error' => null];
                } elseif (method_exists($object, $request['method'])) {
                    $result = call_user_func_array([$object, $request['method']], $request['params']);
                    $response = ['jsonrpc' => '2.0', 'id' => $request['id'], 'result' => $result, 'error' => null];
                } else {
                    $info = lang('method not exists: %s::%s', [class_basename($object), $request['method']]);
                    $error = ['code' => '-32601', 'message' => $info, 'meaning' => lang('The method does not exist or is invalid.')];
                    $response = ['jsonrpc' => '2.0', 'id' => $request['id'], 'result' => null, 'error' => $error];
                }
            } catch (\think\admin\Exception $exception) {
                $error = ['code' => $exception->getCode(), 'message' => lang($exception->getMessage()), 'meaning' => lang('Business Exception.')];
                $response = ['jsonrpc' => '2.0', 'id' => $request['id'], 'result' => $exception->getData(), 'error' => $error];
            } catch (\Exception $exception) {
                $error = ['code' => $exception->getCode(), 'message' => lang($exception->getMessage()), 'meaning' => lang('System Exception.')];
                $response = ['jsonrpc' => '2.0', 'id' => $request['id'], 'result' => null, 'error' => $error];
            }
            // Output the response
            throw new HttpResponseException(json($response));
        }
    }

    /**
     * 打印输出对象方法
     * @param mixed $object
     */
    protected function printMethod($object)
    {
        try {
            $object = new \ReflectionClass($object);
            echo "<h2>{$object->getName()}</h2><hr>";
            foreach ($object->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if (stripos($method->getName(), '_') === 0) continue;
                $params = [];
                foreach ($method->getParameters() as $parameter) {
                    $type = $parameter->getType();
                    if ($type instanceof \ReflectionType) $type = $type->getName();
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
}