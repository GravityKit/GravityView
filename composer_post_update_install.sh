#!/bin/bash

FOUNDATION_NAMESPACE="GravityKit\GravityView"
VENDOR_FOLDERS_TO_KEEP=("composer")

# Namespace Laravel's helper functions in Foundation as they are otherwise globally declared and can cause conflicts
if [ -f "vendor_prefixed/illuminate/support/helpers.php" ]; then
  insertion="${FOUNDATION_NAMESPACE}\Foundation\ThirdParty\Illuminate\Support"
  insertion="\nnamespace ${insertion//\\/\\\\\\};\n" # Escape backslashes for sed

  sed -i ''$([ "$(uname)" = "Darwin" ] && echo '') \
      -e "1s/^//p; 1s/^.*/${insertion}/" \
      vendor_prefixed/illuminate/support/helpers.php
fi

# Keep only the essential dependencies/folders in the vendor directory
if [[ -d "vendor" && "${COMPOSER_DEV_MODE}" -eq 0 ]]; then
  find ./vendor -mindepth 1 -maxdepth 1 -type d $(printf -- "-not -name %s " "${VENDOR_FOLDERS_TO_KEEP[@]}") -exec rm -rf '{}' \;
fi

composer dump-autoload -o
