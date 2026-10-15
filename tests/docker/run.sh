#!/usr/bin/env bash
set -euo pipefail

# 仅挂载已跟踪源码和未忽略的新源码；不挂载 .env、vendor 或宿主数据库。
root=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)
if [[ $# == 0 ]]; then
    [[ -z ${THINKADMIN_ACCEPTANCE_LOCK:-} ]] || { echo "指定锁文件时必须选择单组矩阵" >&2; exit 1; }
    result=0
    for entry in '7.2 8.4 full' '8.2 8.4 full' '7.2 5.7 upgrade' '8.2 8.0 upgrade'; do
        read -r php_version mysql_version scope <<< "$entry"
        bash "$root/tests/docker/run.sh" "$php_version" "$mysql_version" "$scope" || result=1
    done
    exit "$result"
fi
php_version=${1:?}
mysql_version=${2:?}
scope=${3:-full}
[[ $php_version == 7.2 || $php_version == 8.2 ]]
[[ $mysql_version == 5.7 || $mysql_version == 8.0 || $mysql_version == 8.4 ]]
[[ $scope == full || $scope == upgrade ]]

mkdir -p "$root/runtime/acceptance"
evidence=$(mktemp -d "$root/runtime/acceptance/php$php_version-mysql$mysql_version.XXXXXX")
workspace=$(mktemp -d "${TMPDIR:-/tmp}/thinkadmin-acceptance.XXXXXX")
instance="thinkadmin-acceptance-$(basename "$workspace" | tr '[:upper:]' '[:lower:]')"
# shellcheck disable=SC2329
cleanup() {
    local exit_code=$?
    docker rm -f "$instance-php" "$instance-release" "$instance-db" >/dev/null 2>&1 || true
    # 路径来自本次 mktemp，且不会接收外部输入。
    rm -rf -- "$workspace"
    printf '%s\n' "$exit_code" > "$evidence/exit-code.txt"
    echo "验收证据：$evidence"
    exit "$exit_code"
}
trap cleanup EXIT
# 宿主还会追加发布结果，预建文件以避免 Linux 上容器 root 创建的文件拒绝宿主写入。
touch "$evidence/results.tsv"
git -C "$root" rev-parse HEAD > "$evidence/source-sha.txt"
printf '{"php":"%s","mysql":"%s","scope":"%s"}\n' "$php_version" "$mysql_version" "$scope" > "$evidence/matrix.json"
git -C "$root" status --porcelain > "$evidence/worktree.txt"
git -C "$root" diff --binary HEAD > "$evidence/source.patch"
git -C "$root" ls-files -z --cached --others --exclude-standard |
    tar -C "$root" --null -T - -cf "$workspace/source.tar"
shasum -a 256 "$workspace/source.tar" > "$evidence/source-archive.sha256"

composer_version=2.8.12
phpunit_version=11.5.50
if [[ $php_version == 7.2 ]]; then composer_version=2.2.26; phpunit_version=8.5.52; fi
image="thinkadmin-acceptance:$php_version"
docker build --force-rm --build-arg "PHP_SERIES=$php_version" --build-arg "COMPOSER_VERSION=$composer_version" \
    --build-arg "PHPUNIT_VERSION=$phpunit_version" -t "$image" "$root/tests/docker" > "$evidence/build.log" 2>&1 || {
    cat "$evidence/build.log"
    exit 1
}
mysql_image="public.ecr.aws/docker/library/mysql:$mysql_version"
options=(--default-authentication-plugin=mysql_native_password)
platform=(--platform "$(docker version --format '{{.Server.Os}}/{{.Server.Arch}}')")
if [[ $mysql_version == 8.4 ]]; then options=(--mysql-native-password=ON); fi
if [[ $mysql_version == 5.7 ]]; then mysql_image="public.ecr.aws/docker/library/mysql:5.7.44"; platform=(--platform linux/amd64); fi
docker run --detach --name "$instance-db" "${platform[@]}" --tmpfs /var/lib/mysql:rw \
    --env MYSQL_ALLOW_EMPTY_PASSWORD=yes --env MYSQL_ROOT_HOST=% --env MYSQL_DATABASE=phinx_fixture \
    "$mysql_image" "${options[@]}" > /dev/null
ready=false
for ((attempt=0; attempt<90; attempt++)); do
    if docker exec "$instance-db" mysqladmin --protocol=tcp --host=127.0.0.1 ping --silent >/dev/null 2>&1; then
        ready=true
        break
    fi
    sleep 2
done
[[ $ready == true ]] || { docker logs "$instance-db"; exit 1; }
docker exec "$instance-db" mysql -uroot -e "ALTER USER 'root'@'%' IDENTIFIED WITH mysql_native_password BY ''; ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '';"
docker image inspect "$image" "$mysql_image" --format '{{.Id}} {{json .RepoDigests}}' > "$evidence/images.txt"

# 与专用数据库共享网络命名空间，使现有验收继续使用 127.0.0.1，无需增加连接配置。
docker create --name "$instance-php" --network "container:$instance-db" \
    --env THINKADMIN_ACCEPTANCE_CONTAINER=1 --env PHINX_TEST_MYSQL_PORT=3306 \
    --mount "type=bind,source=$evidence,target=/evidence" \
    "$image" bash tests/docker/inside.sh "$scope" > /dev/null
mkdir -p "$workspace/project"
tar -xf "$workspace/source.tar" -C "$workspace/project"
docker cp "$workspace/project/." "$instance-php:/work/project"
if [[ -n ${THINKADMIN_ACCEPTANCE_LOCK:-} ]]; then
    [[ -f $THINKADMIN_ACCEPTANCE_LOCK ]] || { echo "验收锁文件不存在" >&2; exit 1; }
    docker cp "$THINKADMIN_ACCEPTANCE_LOCK" "$instance-php:/work/project/composer.lock"
fi
status=0
docker start --attach "$instance-php" || status=$?
if [[ $php_version == 7.2 && $mysql_version == 8.4 ]]; then
    release_status=0
    if docker build --force-rm --quiet --platform linux/amd64 -f "$root/tests/docker/Dockerfile.release" \
        -t thinkadmin-release-check:local "$root/tests/docker" > "$evidence/release-build.log" 2>&1; then
        docker image inspect thinkadmin-release-check:local --format '{{.Id}} {{json .RepoDigests}}' >> "$evidence/images.txt"
        docker create --name "$instance-release" --platform linux/amd64 --network none \
            thinkadmin-release-check:local php tests/check-release.php > /dev/null
        docker cp "$workspace/project/." "$instance-release:/work/project"
        docker start --attach "$instance-release" > "$evidence/release.log" 2>&1 || release_status=$?
    else
        cp "$evidence/release-build.log" "$evidence/release.log"
        release_status=1
    fi
    cat "$evidence/release.log"
    printf 'release\t%s\n' "$release_status" >> "$evidence/results.tsv"
    if [[ $release_status != 0 ]]; then status=1; fi
fi
exit "$status"
