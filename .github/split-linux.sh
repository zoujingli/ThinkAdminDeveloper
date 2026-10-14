#!/usr/bin/env bash
set -euo pipefail

BASEPATH=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
cd "$BASEPATH"
CURRENT_BRANCH=${GITHUB_REF_NAME:-$(git symbolic-ref --quiet --short HEAD)}
SOURCE_COMMIT=$(git rev-parse HEAD)
git check-ref-format "refs/heads/$CURRENT_BRANCH"

# 流水线已验收当前提交，不再 pull 混入尚未验收的代码；过期任务直接跳过分发。
if [[ -n ${GITHUB_SHA:-} ]]; then
    [[ $SOURCE_COMMIT == "$GITHUB_SHA" ]] || {
        echo "Checkout does not match the tested commit." >&2
        exit 1
    }
    latest=$(git ls-remote origin "refs/heads/$CURRENT_BRANCH" | cut -f1)
    if [[ $latest != "$SOURCE_COMMIT" ]]; then
        echo "A newer source commit exists; skipping this split."
        exit 0
    fi
fi

REPOS=("$@")
if [[ ${#REPOS[@]} -eq 0 ]]; then
    for manifest in "$BASEPATH"/plugin/*/composer.json; do
        REPOS+=("$(basename "$(dirname "$manifest")")")
    done
fi
for repo in "${REPOS[@]}"; do
    if [[ ! $repo =~ ^think-[a-z0-9-]+$ || ! -f "$BASEPATH/plugin/$repo/composer.json" ]]; then
        echo "Unknown plugin: $repo" >&2
        exit 1
    fi
done

for repo in "${REPOS[@]}"; do
    target="git@github.com:zoujingli/$repo.git"
    previous=$(git ls-remote "$target" "refs/heads/$CURRENT_BRANCH" | cut -f1)
    split_commit=$(.github/splitsh-lite-linux --prefix="plugin/$repo")
    # 允许拆分历史更新，但拒绝覆盖读取目标分支后出现的并发提交。
    git push "--force-with-lease=refs/heads/$CURRENT_BRANCH:$previous" "$target" "$split_commit:refs/heads/$CURRENT_BRANCH"
done
