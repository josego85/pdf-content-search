#!/bin/bash
# Warns when a new PHP file is missing declare(strict_types=1).
# Required in every PHP file per CLAUDE.md coding standards.

FILE=$(jq -r '.tool_input.file_path // empty' 2>/dev/null)

[[ -z "$FILE" ]] && exit 0
[[ "$FILE" != *.php ]] && exit 0
[[ ! -f "$FILE" ]] && exit 0

if ! grep -q 'declare(strict_types=1)' "$FILE"; then
    echo "HOOK WARNING: $(basename "$FILE") is missing declare(strict_types=1)."
    echo "Required in every PHP file per CLAUDE.md coding standards."
fi

exit 0
