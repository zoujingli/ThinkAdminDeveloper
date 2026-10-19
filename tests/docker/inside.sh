#!/usr/bin/env bash
set -euo pipefail

cd /work/project
scope=${1:-full}
php tests/docker/prepare.php
operation=(update --with-all-dependencies)
if [[ -f composer.lock ]]; then operation=(install); fi
# 仅重试无安装钩子的依赖下载；真实更新及迁移不会在这里自动重试。
resolved=false
for attempt in 1 2 3; do
    if composer "${operation[@]}" --no-dev --no-plugins --no-scripts --no-interaction --prefer-dist --no-progress > /evidence/resolve.log 2>&1; then
        resolved=true
        break
    fi
    echo "依赖准备失败，第 $attempt 次；诊断保存在 resolve.log"
done
[[ $resolved == true ]] || { cat /evidence/resolve.log; exit 1; }
cp composer.lock /evidence/composer.lock
php -v > /evidence/php-version.txt
php-fpm -v > /evidence/fpm-version.txt
nginx -v > /evidence/nginx-version.txt 2>&1
composer --version > /evidence/composer-version.txt
composer show --locked --format=json > /evidence/dependencies.json

# 不因单一阻断项跳过其余独立验收；全部退出状态写入证据目录。
result=0
check() {
    local name=$1
    shift
    local status=0
    "$@" > "/evidence/$name.log" 2>&1 || status=$?
    printf 'CHECK %s: exit=%s\n' "$name" "$status"
    if [[ $status != 0 ]]; then tail -60 "/evidence/$name.log"; fi
    printf '%s\t%s\n' "$name" "$status" >> /evidence/results.tsv
    if [[ $status != 0 ]]; then result=1; fi
}
syntax_exclusions=()
if [[ $(php -r 'echo PHP_MAJOR_VERSION;') == 7 ]]; then
    syntax_exclusions=(! -path './plugin/think-plugs-helper/*')
fi
check syntax bash -o pipefail -c 'find . -type f -name "*.php" ! -path "*/vendor/*" ! -path "./runtime/*" "$@" -print0 | xargs -0 -n1 php -l' _ "${syntax_exclusions[@]}"
# shellcheck disable=SC2016
check metadata bash -c 'for manifest in composer.json plugin/*/composer.json; do composer validate --no-check-publish "$manifest" || exit $?; done'
if [[ $scope == full ]]; then
    check regression php tests/run.php
else
    check upgrade php tests/check-upgrade.php mysql
fi
check file-distinct-sqlite php tests/check-file-distinct.php sqlite
check file-distinct-mysql php tests/check-file-distinct.php mysql
check console php tests/check-console.php
check composer-sqlite php tests/check-composer.php sqlite
check composer-mysql php tests/check-composer.php mysql
check http-sqlite php tests/check-lifecycle.php sqlite web
check http-mysql php tests/check-lifecycle.php mysql web
check lifecycle php tests/check-lifecycle.php sqlite worker
check http-client php /opt/phpunit.phar -c plugin/think-library/phpunit.xml.dist --filter HttpExtendTest
check minio php tests/check-minio.php
if [[ $(php -r 'echo PHP_MAJOR_VERSION;') == 8 && $scope == full ]]; then
    check dev-dependencies composer install --no-plugins --no-scripts --no-interaction --prefer-dist --no-progress
    check style vendor/bin/php-cs-fixer fix --dry-run --diff --using-cache=no --config=.php-cs-fixer.php --path-mode=override \
        plugin/think-plugs-admin/src/controller/api/Upload.php \
        plugin/think-plugs-admin/src/controller/Config.php \
        plugin/think-plugs-admin/src/controller/api/System.php \
        plugin/think-plugs-admin/src/service/UploadSecurity.php \
        plugin/think-plugs-admin/tests tests .github/verify-acceptance.php \
        plugin/think-library/src/Storage.php \
        plugin/think-library/src/extend/HttpExtend.php \
        plugin/think-library/src/storage/MinioStorage.php \
        plugin/think-library/src/storage/LocalStorage.php \
        plugin/think-library/tests
fi
exit "$result"
