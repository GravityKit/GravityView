#!/bin/bash

VENDOR_FOLDERS_TO_KEEP=("composer")

# Keep only the essential dependencies/folders in the vendor directory
if [[ -d "vendor" && "${COMPOSER_DEV_MODE}" -eq 0 ]]; then
  find ./vendor -mindepth 1 -maxdepth 1 -type d $(printf -- "-not -name %s " "${VENDOR_FOLDERS_TO_KEEP[@]}") -exec rm -rf '{}' \;
fi
