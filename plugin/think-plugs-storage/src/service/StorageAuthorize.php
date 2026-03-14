<?php

declare(strict_types=1);

namespace plugin\storage\service;

use plugin\storage\service\AliossStorage;
use plugin\storage\service\AlistStorage;
use plugin\storage\service\LocalStorage;
use plugin\storage\service\QiniuStorage;
use plugin\storage\service\TxcosStorage;
use plugin\storage\service\UpyunStorage;

class StorageAuthorize
{
    public static function local(LocalStorage $storage, string $key, bool $safe = false, ?string $attname = null): array
    {
        return [
            'url' => $storage->url($key, $safe, $attname),
            'server' => $storage->upload(),
        ];
    }

    public static function qiniu(QiniuStorage $storage, string $key, bool $safe = false, ?string $attname = null): array
    {
        return [
            'url' => $storage->url($key, $safe, $attname),
            'token' => $storage->token($key, 3600, $attname),
            'server' => $storage->upload(),
        ];
    }

    public static function alioss(AliossStorage $storage, string $key, bool $safe = false, ?string $attname = null): array
    {
        $token = $storage->token($key, 3600, $attname);
        return [
            'url' => $token['siteurl'],
            'policy' => $token['policy'],
            'signature' => $token['signature'],
            'OSSAccessKeyId' => $token['keyid'],
            'server' => $storage->upload(),
        ];
    }

    public static function txcos(TxcosStorage $storage, string $key, bool $safe = false, ?string $attname = null): array
    {
        $token = $storage->token($key, 3600, $attname);
        return [
            'url' => $token['siteurl'],
            'q-ak' => $token['q-ak'],
            'policy' => $token['policy'],
            'q-key-time' => $token['q-key-time'],
            'q-signature' => $token['q-signature'],
            'q-sign-algorithm' => $token['q-sign-algorithm'],
            'server' => $storage->upload(),
        ];
    }

    public static function upyun(UpyunStorage $storage, string $key, bool $safe = false, ?string $attname = null, string $hash = ''): array
    {
        $token = $storage->token($key, 3600, $attname, $hash);
        return [
            'url' => $token['siteurl'],
            'policy' => $token['policy'],
            'server' => $storage->upload(),
            'authorization' => $token['authorization'],
        ];
    }

    public static function alist(AlistStorage $storage, string $key): array
    {
        return [
            'url' => $storage->url($key),
            'server' => $storage->upload(),
            'filepath' => $storage->real($key),
            'authorization' => $storage->token(),
        ];
    }
}
