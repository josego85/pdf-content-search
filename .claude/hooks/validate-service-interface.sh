#!/bin/bash
# Warns when a new Service class doesn't implement a Contract interface.
# Runs after Write — new file creation only.
# Outputs to stdout so Claude sees the warning and can self-correct.

FILE=$(jq -r '.tool_input.file_path // empty' 2>/dev/null)

[[ -z "$FILE" ]] && exit 0
[[ "$FILE" != */src/Service/*.php ]] && exit 0
[[ ! -f "$FILE" ]] && exit 0

# Skip non-concrete classes
grep -qE '^\s*(interface|trait|enum|abstract class)' "$FILE" && exit 0

if ! grep -qE 'class\s+\w+.*\bimplements\b' "$FILE"; then
    CLASS=$(grep -oE 'class\s+\w+' "$FILE" | head -1 | awk '{print $2}')
    echo "HOOK WARNING: ${CLASS} in src/Service/ does not implement any interface."
    echo "Add a contract in src/Contract/ per CLAUDE.md architecture rules (Interface/Contract Pattern)."
fi

exit 0
