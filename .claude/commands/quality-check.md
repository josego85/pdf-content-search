Run all quality checks and report results. Stop on first failure and explain how to fix it.

## Step 1 — PHPStan (static analysis)

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml exec -T php composer phpstan
```

If it fails: show the errors. Do not proceed to next step.

## Step 2 — PHP-CS-Fixer (code style)

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml exec -T php composer cs-check
```

If it fails: run `composer cs-fix` to fix automatically, then show what changed.

## Step 3 — Rector (modernization)

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml exec -T php composer rector-dry
```

If it finds changes: show them and ask the user whether to apply with `composer rector`.

## Step 4 — Biome (JS/Vue)

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml exec -T php npm run lint
```

If it fails: show the errors and their locations.

## Summary

After all steps, report:
- ✅ Pass or ❌ Fail for each tool
- Total issues found
- Recommended next action
