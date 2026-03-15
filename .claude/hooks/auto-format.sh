#!/bin/bash
# Auto-format files after Claude edits them.
# Runs php-cs-fixer (via Docker) for PHP, Biome for JS/Vue/TS.
# Always exits 0 — never blocks Claude if tooling is unavailable.

FILE=$(jq -r '.tool_input.file_path // empty' 2>/dev/null)

[[ -z "$FILE" ]] && exit 0

PROJECT_ROOT="$(pwd)"
RELATIVE="${FILE#"$PROJECT_ROOT/"}"

case "$FILE" in
    *.php)
        docker compose -f docker-compose.yml -f docker-compose.dev.yml \
            exec -T php vendor/bin/php-cs-fixer fix "$RELATIVE" \
            --quiet --no-interaction 2>/dev/null
        ;;
    *.vue | *.js | *.ts)
        npx biome format --write "$FILE" --quiet 2>/dev/null
        ;;
esac

exit 0
