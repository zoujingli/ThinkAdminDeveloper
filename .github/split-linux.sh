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

# 仅完整、干净且对应同一提交的四组验收可以形成发布凭据。
manifest_dir="${SPLIT_MANIFEST_DIR:-$BASEPATH/runtime/release/$SOURCE_COMMIT}"
mkdir -p "$manifest_dir"
manifest_temp=$(mktemp -d "${TMPDIR:-/tmp}/thinkadmin-split.XXXXXX")
trap 'rm -rf -- "$manifest_temp"' EXIT
php .github/verify-acceptance.php "${THINKADMIN_ACCEPTANCE_DIR:-$BASEPATH/runtime/acceptance}" "$SOURCE_COMMIT" > "$manifest_temp/acceptance.json"
# shellcheck disable=SC2016
acceptance_hash=$(php -r 'echo hash_file("sha256", $argv[1]);' "$manifest_temp/acceptance.json")
printf 'source\t%s\nbranch\t%s\nacceptance\t%s\n' "$SOURCE_COMMIT" "$CURRENT_BRANCH" "$acceptance_hash" > "$manifest_temp/split.tsv"

for repo in "${REPOS[@]}"; do
    target="git@github.com:zoujingli/$repo.git"
    previous=$(git ls-remote "$target" "refs/heads/$CURRENT_BRANCH" | cut -f1)
    if ! split_commit=$(.github/splitsh-lite-linux --prefix="plugin/$repo"); then
        echo "$repo: splitsh failed: $split_commit" >&2
        exit 1
    fi
    [[ $split_commit =~ ^[0-9a-f]{40}$ ]] || { echo "$repo: invalid split commit." >&2; exit 1; }
    # 旧版 --commit 是拆分范围起点，不是目标提交；直接校验检出状态和实际目录树。
    [[ $(git rev-parse HEAD) == "$SOURCE_COMMIT" ]] || { echo "Source changed during split." >&2; exit 1; }
    [[ $(git rev-parse "$split_commit^{tree}") == "$(git rev-parse "$SOURCE_COMMIT:plugin/$repo")" ]] || {
        echo "$repo: split tree does not match tested source." >&2
        exit 1
    }
    # 允许拆分历史更新，但拒绝覆盖读取目标分支后出现的并发提交。
    git push "--force-with-lease=refs/heads/$CURRENT_BRANCH:$previous" "$target" "$split_commit:refs/heads/$CURRENT_BRANCH"
    printf 'plugin\t%s\t%s\n' "$repo" "$split_commit" >> "$manifest_temp/split.tsv"
done

# 全部推送成功后才输出版本映射，部分成功的拆分不能据此发版。
mv "$manifest_temp/acceptance.json" "$manifest_dir/acceptance.json"
mv "$manifest_temp/split.tsv" "$manifest_dir/split.tsv"
echo "Release manifest: $manifest_dir/split.tsv"
