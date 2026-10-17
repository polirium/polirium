#!/usr/bin/env bash
# Build the self-contained release package consumed by the in-app updater:
#   dist/polirium-<version>.zip and dist/polirium-<version>.zip.sha256
#
# Run from the repository root with submodules checked out and production
# dependencies installed (composer install --no-dev --optimize-autoloader).
# Usage: scripts/release/build-package.sh <version>
set -euo pipefail

VERSION="${1:?usage: build-package.sh <version>}"
VERSION="${VERSION#v}"
ROOT="$(pwd)"
DIST="$ROOT/dist"
STAGE="$DIST/stage/polirium-$VERSION"

if [ "$(tr -d '[:space:]' < VERSION)" != "$VERSION" ]; then
    echo "VERSION file ($(cat VERSION)) does not match release $VERSION" >&2
    exit 1
fi

[ -f vendor/autoload.php ] || { echo "vendor/ missing: run composer install --no-dev first" >&2; exit 1; }
[ -f platform/core/composer.json ] || { echo "submodules missing: git submodule update --init --recursive" >&2; exit 1; }

rm -rf "$DIST" && mkdir -p "$STAGE"

# Tracked files of the repository and every submodule, minus development-only paths.
git ls-files --recurse-submodules -z \
    | grep -zvE '^(tests/|\.github/|plans/|wiki/|artifacts/|\.understand-anything/|POLIRIUM_FULL_AUDIT_PLAN\.md$|phpunit\.xml$|\.editorconfig$|\.gitattributes$|\.gitignore$|\.gitmodules$|\.env\.example$|\.cursorrules$|\.mcp\.json$|boost\.json$|.*/tests/)' \
    | while IFS= read -r -d '' file; do
        [ -e "$file" ] || continue
        mkdir -p "$STAGE/$(dirname "$file")"
        cp -P "$file" "$STAGE/$file"
    done

cp .env.example "$STAGE/.env.example"
cp -R vendor "$STAGE/vendor"
find "$STAGE/vendor" -type d \( -name '.git' -o -name 'tests' -o -name 'Tests' \) -prune -exec rm -rf {} + 2>/dev/null || true

# Empty runtime directories expected by Laravel.
for dir in storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache; do
    mkdir -p "$STAGE/$dir"
done

PHP_MIN="$(php -r '$c=json_decode(file_get_contents("composer.json"),true); preg_match("/\d+\.\d+(\.\d+)?/", $c["require"]["php"] ?? "8.3", $m); echo $m[0] ?? "8.3";')"
# config/installer.php calls Laravel helpers; stub them to read the extension list.
EXTENSIONS="$(php -r 'function storage_path($p = "") { return $p; } function env($k, $d = null) { return $d; } $c = require "config/installer.php"; echo json_encode(array_values($c["requirements"]["extensions"] ?? []));')"
[ "$EXTENSIONS" != "[]" ] || { echo "could not read required PHP extensions" >&2; exit 1; }
COMMIT="$(git rev-parse HEAD)"

cat > "$STAGE/release.json" <<JSON
{
    "name": "polirium",
    "version": "$VERSION",
    "commit": "$COMMIT",
    "built_at": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
    "requires": {
        "php": "$PHP_MIN",
        "extensions": $EXTENSIONS
    }
}
JSON

ZIP="$DIST/polirium-$VERSION.zip"
# -y keeps vendor/polirium/* → platform/* as symlinks instead of a second copy;
# the updater recreates them after extracting.
(cd "$STAGE" && zip -qry -X "$ZIP" .)
(cd "$DIST" && sha256sum "polirium-$VERSION.zip" > "polirium-$VERSION.zip.sha256")
rm -rf "$DIST/stage"

echo "Built $ZIP ($(du -h "$ZIP" | cut -f1))"
cat "$DIST/polirium-$VERSION.zip.sha256"
