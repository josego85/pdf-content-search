#!/bin/bash
# Blocks Claude from reading gitignored env files via Read or Grep.
# Gitignored = contains real secrets (.env.local, .env.prod.local, etc.)
# Committed env files (.env) are safe — no real secrets by convention.

# Read uses file_path; Grep uses path
FILE=$(jq -r '.tool_input.file_path // .tool_input.path // empty' 2>/dev/null)

[[ -z "$FILE" ]] && exit 0
[[ ! "$FILE" == *.env* ]] && exit 0

if git check-ignore -q "$FILE" 2>/dev/null; then
    echo "HOOK BLOCKED: $(basename "$FILE") is gitignored and may contain real secrets."
    echo "Do not read secret files. Ask the user which variables are needed instead."
    exit 2
fi

exit 0
