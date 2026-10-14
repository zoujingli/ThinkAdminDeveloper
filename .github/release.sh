#!/usr/bin/env bash
set -euo pipefail

# 先校验验收凭据和全部目标，再发布固定提交；--dry-run 不创建或推送标签。
DRY_RUN=false
MANIFEST=
while [[ ${1:-} == --* ]]; do
    case "$1" in
        --dry-run) DRY_RUN=true; shift ;;
        --manifest) MANIFEST=${2:?Missing manifest}; shift 2 ;;
        *) echo "Unknown option: $1" >&2; exit 1 ;;
    esac
done
if [[ $# -eq 0 ]]; then
    echo "Usage: $0 [--dry-run] --manifest <split.tsv> <v-tag> [plugin ...]" >&2
    exit 1
fi

VERSION=$1
shift
if [[ $VERSION != v* ]] || ! git check-ref-format "refs/tags/$VERSION"; then
    echo "Invalid release tag: $VERSION" >&2
    exit 1
fi
BASEPATH=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
CURRENT_BRANCH=$(git -C "$BASEPATH" symbolic-ref --quiet --short HEAD) || {
    echo "Release requires a checked-out branch." >&2
    exit 1
}
[[ -f $MANIFEST ]] || { echo "A verified split manifest is required (--manifest)." >&2; exit 1; }
[[ -z $(git -C "$BASEPATH" status --porcelain) ]] || { echo "Release requires a clean source checkout." >&2; exit 1; }
source_commit=$(git -C "$BASEPATH" rev-parse HEAD)
manifest_source=
manifest_branch=
acceptance_hash=
while IFS=$'\t' read -r kind value extra; do
    case "$kind" in
        source) [[ -z $manifest_source && -z $extra ]] || exit 1; manifest_source=$value ;;
        branch) [[ -z $manifest_branch && -z $extra ]] || exit 1; manifest_branch=$value ;;
        acceptance) [[ -z $acceptance_hash && -z $extra ]] || exit 1; acceptance_hash=$value ;;
        plugin) [[ $value =~ ^think-[a-z0-9-]+$ && $extra =~ ^[0-9a-f]{40}$ ]] || exit 1 ;;
        *) echo "Invalid manifest record." >&2; exit 1 ;;
    esac
done < "$MANIFEST"
[[ $manifest_source == "$source_commit" && $manifest_branch == "$CURRENT_BRANCH" ]] || {
    echo "Manifest does not match this source commit and branch." >&2
    exit 1
}
acceptance_file="$(dirname "$MANIFEST")/acceptance.json"
# PHP 代码中的变量必须由 PHP 解释，不能由 shell 展开。
# shellcheck disable=SC2016
php -r '
    $file = $argv[1];
    $data = is_file($file) ? json_decode(file_get_contents($file), true) : [];
    if (($data["status"] ?? "") !== "passed" || ($data["source"] ?? "") !== $argv[2]
        || count($data["matrix"] ?? []) !== 4 || hash_file("sha256", $file) !== $argv[3]) {
        fwrite(STDERR, "Acceptance receipt is missing or inconsistent.\n");
        exit(1);
    }
' "$acceptance_file" "$source_commit" "$acceptance_hash"
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

# 临时目录由本次调用独占，退出时只清理本次克隆，避免并行发布互相删除文件。
release_dir=$(mktemp -d "${TMPDIR:-/tmp}/thinkadmin-release.XXXXXX")
trap 'rm -rf -- "$release_dir"' EXIT
for repo in "${REPOS[@]}"; do
    git clone --quiet --no-checkout "git@github.com:zoujingli/$repo.git" "$release_dir/$repo"
    target=$(git -C "$release_dir/$repo" rev-parse "refs/remotes/origin/$CURRENT_BRANCH^{commit}")
    expected=$(awk -F '\t' -v repo="$repo" '$1 == "plugin" && $2 == repo { print $3 }' "$MANIFEST")
    [[ $expected =~ ^[0-9a-f]{40}$ && $target == "$expected" ]] || {
        echo "$repo: remote branch differs from the tested split; no tags were published." >&2
        exit 1
    }
    tagged=$(git -C "$release_dir/$repo" rev-parse --verify "refs/tags/$VERSION^{commit}" 2>/dev/null || true)
    if [[ -n $tagged && $tagged != "$target" ]]; then
        echo "$repo: $VERSION already points to another commit; no tags were published." >&2
        exit 1
    fi
    echo "$repo: $VERSION -> $target"
done

if [[ $DRY_RUN == true ]]; then
    echo "Dry run passed; no tags were created or pushed."
    exit 0
fi

for repo in "${REPOS[@]}"; do
    if git -C "$release_dir/$repo" rev-parse --verify "refs/tags/$VERSION" >/dev/null 2>&1; then
        echo "$repo: $VERSION already published."
        continue
    fi
    git -C "$release_dir/$repo" tag "$VERSION" "refs/remotes/origin/$CURRENT_BRANCH"
    git -C "$release_dir/$repo" push origin "refs/tags/$VERSION:refs/tags/$VERSION"
done
