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

use think\admin\Controller;
use think\admin\Exception;
use think\admin\extend\CodeToolkit;

/**
 * 标准 JWT 工具。
 * 这里仅保留令牌编码、签名和校验能力，具体认证策略由 `auth` 组合使用。
 */
final class JwtToken
{
    /**
     * JWT 头部参数。
     */
    private const HEADER = ['typ' => 'JWT', 'alg' => 'HS256'];

    /**
     * 支持的签名算法。
     */
    private const SIGN_TYPES = [
        'HS256' => 'sha256',
        'HS384' => 'sha384',
        'HS512' => 'sha512',
    ];

    /**
     * 标准 claim 字段集合。
     */
    private const CLAIM_FIELDS = ['iss', 'sub', 'aud', 'exp', 'iat', 'nbf'];

    /**
     * 是否返回新令牌。
     */
    private static bool $rejwt = false;

    /**
     * 当前请求解析后的令牌数据。
     */
    private static array $input = [];

    /**
     * 是否需要回写新令牌。
     */
    public static function isRejwt(): bool
    {
        return self::$rejwt;
    }

    /**
     * 获取当前请求解析后的令牌数据。
     */
    public static function getInData(): array
    {
        return self::$input;
    }

    /**
     * 生成 jwt token。
     */
    public static function token(array $data = [], ?string $jwtkey = null, ?bool $rejwt = null): string
    {
        $jwtkey = self::jwtkey($jwtkey);
        if (is_bool($rejwt)) {
            self::$rejwt = $rejwt;
        }
        [$claims, $extra] = self::splitClaims($data);
        $claims['enc'] = CodeToolkit::encrypt(json_encode($extra, JSON_UNESCAPED_UNICODE), $jwtkey);
        $header = CodeToolkit::enSafe64(json_encode(self::HEADER, JSON_UNESCAPED_UNICODE));
        $payload = CodeToolkit::enSafe64(json_encode($claims, JSON_UNESCAPED_UNICODE));
        return "{$header}.{$payload}." . self::withSign("{$header}.{$payload}", self::HEADER['alg'], $jwtkey);
    }

    /**
     * 获取 JWT 密钥。
     */
    public static function jwtkey(?string $jwtkey = null): string
    {
        try {
            if (!empty($jwtkey)) {
                return $jwtkey;
            }
            $jwtkey = config('app.jwtkey');
            if (!empty($jwtkey)) {
                return $jwtkey;
            }
            $jwtkey = sysconf('data.jwtkey|raw');
            if (!empty($jwtkey)) {
                return $jwtkey;
            }
            $jwtkey = bin2hex(random_bytes(16));
            sysconf('data.jwtkey', $jwtkey);
            return $jwtkey;
        } catch (\Exception $exception) {
            trace_file($exception);
            return 'thinkadmin';
        }
    }

    /**
     * 验证 token 是否有效，默认验证 exp、nbf、iat 时间。
     *
     * @throws Exception
     */
    public static function verify(string $token, ?string $jwtkey = null): array
    {
        [$base64header, $base64payload, $signature] = self::splitToken($token);
        $header = self::decodeSegment($base64header);
        if (empty($header['alg'])) {
            throw new Exception('数据解密失败！', 0, []);
        }
        $jwtkey = self::jwtkey($jwtkey);
        if (self::withSign("{$base64header}.{$base64payload}", $header['alg'], $jwtkey) !== $signature) {
            throw new Exception('验证签名失败！', 0, []);
        }
        $payload = self::decodeSegment($base64payload);
        self::validatePayload($payload);
        $extra = [];
        if (isset($payload['enc'])) {
            $extra = json_decode(CodeToolkit::decrypt($payload['enc'], $jwtkey), true) ?: [];
            unset($payload['enc']);
        }
        return self::$input = array_merge($payload, $extra);
    }

    /**
     * 输出模板变量。
     * 这是旧接口能力，仍然保留在这里，避免影响现有 API 控制器。
     */
    public static function fetch(Controller $class, array $vars = [])
    {
        $ignore = array_keys(get_class_vars(Controller::class));
        foreach ($class as $name => $value) {
            if (!in_array($name, $ignore, true)) {
                if (is_array($value) || is_numeric($value) || is_string($value) || is_bool($value) || is_null($value)) {
                    $vars[$name] = $value;
                }
            }
        }
        $class->success('获取变量成功！', $vars);
    }

    /**
     * 拆分 JWT 标准 claim 与业务扩展数据。
     */
    private static function splitClaims(array $data): array
    {
        $claims = ['iat' => time()];
        foreach ($data as $k => $v) {
            if (in_array($k, self::CLAIM_FIELDS, true)) {
                $claims[$k] = $v;
                unset($data[$k]);
            }
        }
        return [$claims, $data];
    }

    /**
     * 生成数据签名。
     */
    private static function withSign(string $input, string $alg = 'HS256', ?string $key = null): string
    {
        return CodeToolkit::enSafe64(hash_hmac(self::SIGN_TYPES[$alg], $input, self::jwtkey($key), true));
    }

    /**
     * 拆分 token 三段结构。
     *
     * @throws Exception
     */
    private static function splitToken(string $token): array
    {
        $tokens = explode('.', $token);
        if (count($tokens) !== 3) {
            throw new Exception('数据解密失败！', 0, []);
        }
        return $tokens;
    }

    /**
     * 解码 JWT 段内容。
     */
    private static function decodeSegment(string $segment): array
    {
        return json_decode(CodeToolkit::deSafe64($segment), true) ?: [];
    }

    /**
     * 验证 token 时间有效性。
     *
     * @throws Exception
     */
    private static function validatePayload(array $payload): void
    {
        $time = time();
        if (isset($payload['iat']) && $payload['iat'] > $time) {
            throw new Exception('服务器时间验证失败！', 0, $payload);
        }
        if (isset($payload['exp']) && $payload['exp'] < $time) {
            throw new Exception('服务器时间验证失败！', 0, $payload);
        }
        if (isset($payload['nbf']) && $payload['nbf'] > $time) {
            throw new Exception('不接收处理该TOKEN', 0, $payload);
        }
    }
}
