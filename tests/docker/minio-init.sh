#!/usr/bin/env bash
set -euo pipefail

# 仅供本次 Docker 临时服务，固定验收账号；宿主 curl 提供独立的 SigV4 签名实现。
endpoint=${1:?}
[[ $endpoint == http://127.0.0.1:* ]]
ready=false
for ((attempt=0; attempt<60; attempt++)); do
    if curl --silent --fail --max-time 2 "$endpoint/minio/health/ready" >/dev/null; then
        ready=true
        break
    fi
    sleep 1
done
[[ $ready == true ]] || { echo 'MinIO 未就绪，存储验收未运行' >&2; exit 1; }
signed=(--silent --show-error --fail --max-time 20 --aws-sigv4 aws:amz:acceptance-1:s3 --user thinkadmin:acceptance-secret)
for bucket in thinkadmin-test thinkadmin-public; do
    curl "${signed[@]}" -X PUT "$endpoint/$bucket"
done
curl "${signed[@]}" -X PUT "$endpoint/thinkadmin-public?policy" --data-binary \
    '{"Version":"2012-10-17","Statement":[{"Effect":"Allow","Principal":{"AWS":["*"]},"Action":["s3:GetObject"],"Resource":["arn:aws:s3:::thinkadmin-public/*"]}]}'
