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
use plugin\storage\service\AliossStorage;
use plugin\storage\service\AlistStorage;
use plugin\storage\service\LocalStorage;
use plugin\storage\service\QiniuStorage;
use plugin\storage\service\StorageAuthorize;
use plugin\storage\service\TxcosStorage;
use plugin\storage\service\UpyunStorage;

return [
    'default' => 'local',
    'global' => [
        'driver' => ['key' => 'storage.driver', 'legacy' => ['storage.type'], 'default' => 'local'],
        'naming' => ['key' => 'storage.naming', 'legacy' => ['storage.name_type'], 'default' => 'xmd5'],
        'link' => ['key' => 'storage.link', 'legacy' => ['storage.link_type'], 'default' => 'none'],
        'allowed_exts' => ['key' => 'storage.allowed_exts', 'legacy' => ['storage.allow_exts'], 'default' => 'doc,gif,ico,jpg,mp3,mp4,p12,pem,png,rar,xls,xlsx'],
    ],
    'drivers' => [
        'local' => [
            'label' => '本地服务器存储',
            'class' => LocalStorage::class,
            'template' => 'storage-local',
            'regions' => [LocalStorage::class, 'region'],
            'authorize' => [StorageAuthorize::class, 'local'],
            'config' => [
                'protocol' => ['key' => 'storage.local.protocol', 'legacy' => ['storage.local_http_protocol'], 'default' => 'follow'],
                'domain' => ['key' => 'storage.local.domain', 'legacy' => ['storage.local_http_domain'], 'default' => ''],
            ],
        ],
        'alist' => [
            'label' => '自建Alist存储',
            'class' => AlistStorage::class,
            'template' => 'storage-alist',
            'regions' => [AlistStorage::class, 'region'],
            'authorize' => [StorageAuthorize::class, 'alist'],
            'config' => [
                'protocol' => ['key' => 'storage.alist.protocol', 'legacy' => ['storage.alist_http_protocol'], 'default' => 'http'],
                'domain' => ['key' => 'storage.alist.domain', 'legacy' => ['storage.alist_http_domain'], 'default' => ''],
                'path' => ['key' => 'storage.alist.path', 'legacy' => ['storage.alist_savepath'], 'default' => ''],
                'username' => ['key' => 'storage.alist.username', 'legacy' => ['storage.alist_username'], 'default' => ''],
                'password' => ['key' => 'storage.alist.password', 'legacy' => ['storage.alist_password'], 'default' => ''],
            ],
        ],
        'qiniu' => [
            'label' => '七牛云对象存储',
            'class' => QiniuStorage::class,
            'template' => 'storage-qiniu',
            'regions' => [QiniuStorage::class, 'region'],
            'authorize' => [StorageAuthorize::class, 'qiniu'],
            'config' => [
                'protocol' => ['key' => 'storage.qiniu.protocol', 'legacy' => ['storage.qiniu_http_protocol'], 'default' => 'http'],
                'region' => ['key' => 'storage.qiniu.region', 'legacy' => ['storage.qiniu_region'], 'default' => ''],
                'bucket' => ['key' => 'storage.qiniu.bucket', 'legacy' => ['storage.qiniu_bucket'], 'default' => ''],
                'domain' => ['key' => 'storage.qiniu.domain', 'legacy' => ['storage.qiniu_http_domain', 'storage.qiniu_domain'], 'default' => ''],
                'access_key' => ['key' => 'storage.qiniu.access_key', 'legacy' => ['storage.qiniu_access_key'], 'default' => ''],
                'secret_key' => ['key' => 'storage.qiniu.secret_key', 'legacy' => ['storage.qiniu_secret_key'], 'default' => ''],
            ],
        ],
        'upyun' => [
            'label' => '又拍云USS存储',
            'class' => UpyunStorage::class,
            'template' => 'storage-upyun',
            'regions' => [UpyunStorage::class, 'region'],
            'authorize' => [StorageAuthorize::class, 'upyun'],
            'config' => [
                'protocol' => ['key' => 'storage.upyun.protocol', 'legacy' => ['storage.upyun_http_protocol'], 'default' => 'http'],
                'bucket' => ['key' => 'storage.upyun.bucket', 'legacy' => ['storage.upyun_bucket'], 'default' => ''],
                'domain' => ['key' => 'storage.upyun.domain', 'legacy' => ['storage.upyun_http_domain'], 'default' => ''],
                'username' => ['key' => 'storage.upyun.username', 'legacy' => ['storage.upyun_access_key'], 'default' => ''],
                'password' => ['key' => 'storage.upyun.password', 'legacy' => ['storage.upyun_secret_key'], 'default' => ''],
            ],
        ],
        'txcos' => [
            'label' => '腾讯云COS存储',
            'class' => TxcosStorage::class,
            'template' => 'storage-txcos',
            'regions' => [TxcosStorage::class, 'region'],
            'authorize' => [StorageAuthorize::class, 'txcos'],
            'config' => [
                'protocol' => ['key' => 'storage.txcos.protocol', 'legacy' => ['storage.txcos_http_protocol'], 'default' => 'http'],
                'region' => ['key' => 'storage.txcos.region', 'legacy' => ['storage.txcos_point'], 'default' => ''],
                'bucket' => ['key' => 'storage.txcos.bucket', 'legacy' => ['storage.txcos_bucket'], 'default' => ''],
                'domain' => ['key' => 'storage.txcos.domain', 'legacy' => ['storage.txcos_http_domain'], 'default' => ''],
                'access_key' => ['key' => 'storage.txcos.access_key', 'legacy' => ['storage.txcos_access_key'], 'default' => ''],
                'secret_key' => ['key' => 'storage.txcos.secret_key', 'legacy' => ['storage.txcos_secret_key'], 'default' => ''],
            ],
        ],
        'alioss' => [
            'label' => '阿里云OSS存储',
            'class' => AliossStorage::class,
            'template' => 'storage-alioss',
            'regions' => [AliossStorage::class, 'region'],
            'authorize' => [StorageAuthorize::class, 'alioss'],
            'config' => [
                'protocol' => ['key' => 'storage.alioss.protocol', 'legacy' => ['storage.alioss_http_protocol'], 'default' => 'http'],
                'region' => ['key' => 'storage.alioss.region', 'legacy' => ['storage.alioss_point'], 'default' => ''],
                'bucket' => ['key' => 'storage.alioss.bucket', 'legacy' => ['storage.alioss_bucket'], 'default' => ''],
                'domain' => ['key' => 'storage.alioss.domain', 'legacy' => ['storage.alioss_http_domain'], 'default' => ''],
                'access_key' => ['key' => 'storage.alioss.access_key', 'legacy' => ['storage.alioss_access_key'], 'default' => ''],
                'secret_key' => ['key' => 'storage.alioss.secret_key', 'legacy' => ['storage.alioss_secret_key'], 'default' => ''],
            ],
        ],
    ],
];
