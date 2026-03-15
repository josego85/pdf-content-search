#!/bin/bash
# Warns when a new Service class has no corresponding Unit test.
# Every new service must have a Unit test per CLAUDE.md testing rules.

FILE=$(jq -r '.tool_input.file_path // empty' 2>/dev/null)

[[ -z "$FILE" ]] && exit 0
[[ "$FILE" != */src/Service/*.php ]] && exit 0
[[ ! -f "$FILE" ]] && exit 0

grep -qE '^\s*(interface|trait|enum|abstract class)' "$FILE" && exit 0

BASENAME=$(basename "$FILE" .php)
TEST_FILE="tests/Unit/Service/${BASENAME}Test.php"

if [[ ! -f "$TEST_FILE" ]]; then
    echo "HOOK WARNING: No unit test found for ${BASENAME}."
    echo "Expected: ${TEST_FILE}"
    echo "Every new service must have a Unit test per CLAUDE.md testing rules."
fi

exit 0
