<?php

declare(strict_types=1);

namespace plugin\system\service;

use think\admin\Service;
use think\admin\service\AppService;
use think\admin\service\RuntimeService;

/**
 * 登录页服务。
 * @class LoginService
 */
class LoginService extends Service
{
    /**
     * @return array<string, mixed>
     */
    public static function buildPageContext(string $token, string $passwordKey, array $images = []): array
    {
        $site = ConfigService::getSiteConfig();

        $images = array_values(array_filter(array_map('strval', $images !== [] ? $images : (array)$site['login_background_images'])));
        if ($images === []) {
            $images = [
                SystemService::uri('/static/theme/img/login/bg1.jpg'),
                SystemService::uri('/static/theme/img/login/bg2.jpg'),
            ];
        }

        return [
            'title' => strval(lang('系统登录')),
            'pageTitle' => strval(lang('系统登录')),
            'staticRoot' => AppService::uri('static'),
            'loginToken' => $token,
            'loginPasswordKey' => $passwordKey,
            'runtimeMode' => RuntimeService::check(),
            'tokenValueJson' => json_encode('', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'websiteName' => strval($site['website_name'] ?? 'ThinkAdmin'),
            'applicationName' => strval($site['application_name'] ?? 'ThinkAdmin'),
            'applicationVersion' => strval($site['application_version'] ?? ''),
            'loginTitle' => strval($site['login_title'] ?? lang('系统管理')),
            'copyrightText' => strval($site['copyright'] ?? ''),
            'publicSecurityFiling' => strval($site['public_security_filing'] ?? ''),
            'miitFiling' => strval($site['miit_filing'] ?? ''),
            'homeUrl' => url('@')->build(),
            'loginSliderUrl' => url('system/login/slider', [], false),
            'loginCheckUrl' => url('system/login/check', [], false),
            'loginStyle' => sprintf('style="background-image:url(%s)" data-bg-transition="%s"', $images[0], join(',', $images)),
        ];
    }

    public static function syncSiteHost(string $domain): void
    {
        ConfigService::syncSiteHost($domain);
    }

    /**
     * @return array<string, string>
     */
    public static function noStoreHeaders(): array
    {
        return [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];
    }
}
