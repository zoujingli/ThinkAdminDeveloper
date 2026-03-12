<?php

declare(strict_types=1);

namespace think\admin\extend\rpc;

use think\admin\Exception;
use think\admin\extend\http\HttpClient;

/**
 * JSON-RPC 客户端实现。
 * 统一改用 HttpClient 发送请求，不再在这里单独维护一套 stream 上下文逻辑。
 */
class JsonRpcHttpClient
{
    /**
     * 请求 ID。
     */
    private int $id;

    /**
     * 服务端地址。
     */
    private string $proxy;

    /**
     * 请求头部参数。
     */
    private array $header;

    public function __construct(string $proxy, array $header = [])
    {
        $this->id = time();
        $this->proxy = $proxy;
        $this->header = $header;
    }

    /**
     * 执行 JSON-RPC 请求。
     *
     * @return mixed
     * @throws Exception
     */
    public function __call(string $method, array $params = [])
    {
        $request = json_encode([
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => $this->id,
        ], JSON_UNESCAPED_UNICODE);

        $content = HttpClient::post($this->proxy, $request, [
            'timeout' => 60,
            'returnHeader' => false,
            'headers' => array_merge([
                'Content-Type: application/json',
                'User-Agent: think-admin-jsonrpc',
            ], $this->header),
        ]);
        $response = json_decode((string)$content, true) ?: [];
        if (empty($response)) {
            throw new Exception(lang('Unable connect: %s', [$this->proxy]));
        }
        if (isset($response['code'], $response['info'])) {
            throw new Exception($response['info'], (int)$response['code'], $response['data'] ?? []);
        }
        if (empty($response['id']) || $response['id'] !== $this->id) {
            throw new Exception(lang('Error flag ( Request tag: %s, Response tag: %s )', [$this->id, $response['id'] ?? '-']), 0, $response);
        }
        if (is_null($response['error'] ?? null)) {
            return $response['result'] ?? null;
        }
        throw new Exception($response['error']['message'], (int)$response['error']['code'], $response['result'] ?? []);
    }
}
