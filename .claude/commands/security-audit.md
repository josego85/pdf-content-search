Run security audits on PHP and JS dependencies. Stop on first failure and explain how to fix it.

## Step 1 — Composer audit (PHP)

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml exec -T php composer audit
```

If it fails: show the affected package, CVE ID, severity, and recommended version to upgrade to.

## Step 2 — npm audit (JS)

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml exec -T php npm audit --audit-level=high
```

If it fails: show the affected package, vulnerability type, severity, and run `npm audit fix` if safe to do so.

## Summary

After both steps, report:
- ✅ Pass or ❌ Fail for each tool
- Total vulnerabilities found by severity (critical / high / moderate)
- Recommended next action
