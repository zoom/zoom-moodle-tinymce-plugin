#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
PLUGIN_NAME="$(basename "${PLUGIN_DIR}")"
DIST_DIR="${PLUGIN_DIR}/dist"
OUTPUT_ZIP="${DIST_DIR}/${PLUGIN_NAME}.zip"

if ! command -v zip >/dev/null 2>&1; then
    echo "error: zip command not found" >&2
    exit 1
fi

REQUIRED_PATHS=(
    "version.php"
    "settings.php"
    "launcher.php"
    "prepare.php"
    "LICENSE"
    "classes"
    "lang"
    "pix"
    "amd/build"
)

for required_path in "${REQUIRED_PATHS[@]}"; do
    if [[ ! -e "${PLUGIN_DIR}/${required_path}" ]]; then
        echo "error: missing required path ${required_path}" >&2
        exit 1
    fi
done

rm -rf "${DIST_DIR}"
mkdir -p "${DIST_DIR}"

STAGE_DIR="$(mktemp -d)"
trap 'rm -rf "${STAGE_DIR}"' EXIT

PACKAGE_ROOT="${STAGE_DIR}/${PLUGIN_NAME}"
mkdir -p "${PACKAGE_ROOT}"

copy_path() {
    local relative_path="$1"
    local source_path="${PLUGIN_DIR}/${relative_path}"
    local target_path="${PACKAGE_ROOT}/${relative_path}"

    mkdir -p "$(dirname "${target_path}")"
    cp -R "${source_path}" "${target_path}"
}

copy_path "version.php"
copy_path "settings.php"
copy_path "launcher.php"
copy_path "prepare.php"
copy_path "LICENSE"
copy_path "README.md"
copy_path "classes"
copy_path "lang"
copy_path "pix"
copy_path "amd/build"

(
    cd "${STAGE_DIR}"
    zip -r -q "${OUTPUT_ZIP}" "${PLUGIN_NAME}"
)

echo "Built ${OUTPUT_ZIP}"
