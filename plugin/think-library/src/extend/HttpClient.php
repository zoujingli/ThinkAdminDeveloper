<?php

declare(strict_types=1);

namespace think\admin\extend;

/**
 * 标准 HTTP 客户端工具。
 */
class HttpClient
{
    /**
     * 以 GET 模拟网络请求。
     *
     * @return bool|string
     */
    public static function get(string $location, $data = [], array $options = [])
    {
        $options['query'] = $data;
        return static::request('get', $location, $options);
    }

    /**
     * 以 POST 模拟网络请求。
     *
     * @return bool|string
     */
    public static function post(string $location, $data = [], array $options = [])
    {
        $options['data'] = $data;
        return static::request('post', $location, $options);
    }

    /**
     * 以 FormData 模拟网络请求。
     *
     * @return bool|string
     */
    public static function submit(string $url, array $data = [], array $file = [], array $header = [], string $method = 'POST', bool $returnHeader = true)
    {
        [$lines, $boundary] = [[], CodeToolkit::random(18)];
        foreach ($data as $key => $value) {
            $lines[] = "--{$boundary}";
            $lines[] = "Content-Disposition: form-data; name=\"{$key}\"";
            $lines[] = '';
            $lines[] = $value;
        }
        if (is_array($file) && isset($file['field'], $file['name'])) {
            $lines[] = "--{$boundary}";
            $lines[] = "Content-Disposition: form-data; name=\"{$file['field']}\"; filename=\"{$file['name']}\"";
            if (isset($file['type'])) {
                $lines[] = "Content-Type: \"{$file['type']}\"";
            }
            $lines[] = '';
            $lines[] = $file['content'];
        }
        $lines[] = "--{$boundary}--";
        $header[] = "Content-type:multipart/form-data;boundary={$boundary}";
        return static::request($method, $url, [
            'data' => join("\r\n", $lines),
            'returnHeader' => $returnHeader,
            'headers' => $header,
        ]);
    }

    /**
     * 以 cURL 模拟网络请求。
     *
     * @return bool|string
     */
    public static function request(string $method, string $location, array $options = [])
    {
        $curl = curl_init();
        static::applyCommonOptions($curl, $options);
        static::applyRequestOptions($curl, $method, $options);
        curl_setopt($curl, CURLOPT_URL, static::appendQuery($location, $options['query'] ?? null));
        $content = curl_exec($curl);
        curl_close($curl);
        return $content;
    }

    /**
     * 公共 cURL 参数。
     * 这些参数在整个项目里应该保持一致，避免不同调用点各自拼一套。
     */
    private static function applyCommonOptions($curl, array $options): void
    {
        curl_setopt($curl, CURLOPT_USERAGENT, $options['agent'] ?? static::getUserAgent());
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_TIMEOUT, isset($options['timeout']) && is_numeric($options['timeout']) ? (int)$options['timeout'] : 60);
        curl_setopt($curl, CURLOPT_HEADER, !empty($options['returnHeader']));
    }

    /**
     * 请求级参数设置。
     */
    private static function applyRequestOptions($curl, string $method, array $options): void
    {
        if (!empty($options['cookie'])) {
            curl_setopt($curl, CURLOPT_COOKIE, $options['cookie']);
        }
        if (!empty($options['headers'])) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $options['headers']);
        }
        if (!empty($options['cookie_file'])) {
            curl_setopt($curl, CURLOPT_COOKIEJAR, $options['cookie_file']);
            curl_setopt($curl, CURLOPT_COOKIEFILE, $options['cookie_file']);
        }
        $method = strtolower($method);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        if ($method === 'head') {
            curl_setopt($curl, CURLOPT_NOBODY, true);
        } elseif (array_key_exists('data', $options)) {
            if ($method === 'post') {
                curl_setopt($curl, CURLOPT_POST, true);
            }
            curl_setopt($curl, CURLOPT_POSTFIELDS, $options['data']);
        }
        if (isset($options['setopt']) && is_array($options['setopt'])) {
            foreach ($options['setopt'] as $value) {
                if (is_array($value)) {
                    curl_setopt($curl, ...$value);
                }
            }
        }
    }

    /**
     * 给 URL 安全追加 query 参数。
     */
    private static function appendQuery(string $location, $query): string
    {
        if (empty($query)) {
            return $location;
        }
        $location .= strpos($location, '?') !== false ? '&' : '?';
        if (is_array($query)) {
            return $location . http_build_query($query);
        }
        if (is_string($query)) {
            return $location . $query;
        }
        return $location;
    }

    /**
     * 获取浏览器代理信息。
     */
    private static function getUserAgent(): string
    {
        $agents = [
            'Mozilla/5.0 (Windows NT 6.1; rv:2.0.1) Gecko/20100101 Firefox/4.0.1',
            'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.57 Safari/536.11',
            'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:38.0) Gecko/20100101 Firefox/38.0',
            'Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; .NET4.0C; .NET4.0E; .NET CLR 2.0.50727; .NET CLR 3.0.30729; .NET CLR 3.5.30729; InfoPath.3; rv:11.0) like Gecko',
            'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-us) AppleWebKit/534.50 (KHTML, like Gecko) Version/5.1 Safari/534.50',
            'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0)',
            'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.6; rv:2.0.1) Gecko/20100101 Firefox/4.0.1',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_0) AppleWebKit/535.11 (KHTML, like Gecko) Chrome/17.0.963.56 Safari/535.11',
        ];
        return $agents[array_rand($agents)];
    }
}
