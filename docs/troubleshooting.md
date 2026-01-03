# Troubleshooting Guide

Common issues and solutions for PDF Content Search.

## Analytics Dashboard

### Dashboard Shows No Data

**Problem**: Charts are empty or show "No data available".

**Solutions:**

1. **Verify searches have been logged:**
   ```bash
   docker compose exec php php bin/console dbal:run-sql "SELECT COUNT(*) FROM search_analytics"
   ```

2. **Check Messenger workers are running:**
   ```bash
   docker compose exec php ps aux | grep messenger
   ```

3. **Process pending messages manually:**
   ```bash
   docker compose exec php php bin/console messenger:consume async -vv
   ```

### Slow API Responses

**Problem**: Analytics API takes >500ms to respond.

**Solutions:**

1. **Verify database indices exist:**
   ```bash
   docker compose exec php php bin/console dbal:run-sql "SHOW INDEX FROM search_analytics"
   ```

   Expected indices: `idx_created_at`, `idx_query`, `idx_strategy`

2. **Increase PostgreSQL shared_buffers:**
   ```yaml
   # docker-compose.yml
   postgres:
     command: postgres -c shared_buffers=256MB
   ```

3. **Reduce time range** (use 7 days instead of 90)

### Missing Charts

**Problem**: KPIs load but charts don't appear.

**Solutions:**

1. **Check browser console for JavaScript errors**
   - Open DevTools (F12) → Console tab
   - Look for errors related to ApexCharts or Vue

2. **Verify ApexCharts is installed:**
   ```bash
   docker compose exec php npm list vue3-apexcharts
   ```

3. **Rebuild frontend assets:**
   ```bash
   docker compose exec php npm run build
   ```

---

## PDF Indexing Issues

### No PDF Files Found

**Problem**: Running `app:index-pdfs` reports "No PDF files found" even though PDFs exist in `public/pdfs/`.

**Root Causes:**

1. **Using PRODUCTION mode instead of DEVELOPMENT** (Most common)
2. File permissions issue (Less common)

**Diagnostic Steps:**

1. **Check if files exist INSIDE the container:**
   ```bash
   docker compose exec php ls -la public/pdfs/
   ```

2. **Analyze the output:**
   - **If directory is EMPTY** → You're using production mode (volumes not mounted)
   - **If files exist but show permission denied** → Permissions issue

---

### Solution A: Production vs Development Mode (Most Common)

**Problem**: PDFs exist on host but are invisible inside the container.

**Cause**: In **production mode**, the code is COPIED into the container at build time. Any files you add to `public/pdfs/` on your host machine won't be visible inside the running container.

**Solution:**

1. **Stop current containers:**
   ```bash
   docker compose down
   ```

2. **Start using DEVELOPMENT mode:**
   ```bash
   # Option 1: Use the dev script (recommended)
   ./docker-dev.sh up

   # Option 2: Manual start (auto-loads docker-compose.override.yml)
   docker compose up -d
   ```

3. **Verify PDFs are now visible:**
   ```bash
   docker compose exec php ls -la public/pdfs/
   ```

   You should see your PDF files listed.

4. **Index the PDFs:**
   ```bash
   docker compose exec php php bin/console app:index-pdfs
   ```

**Why this works:**
- Development mode (`docker-compose.override.yml`) mounts the entire project directory as a volume: `. :/var/www/html`
- Changes on your host machine are immediately visible inside the container
- Production mode uses named volumes and doesn't mount source code

---

### Solution B: Fix Permissions (If files exist but can't be read)

**Problem**: Files exist inside the container but have wrong permissions.

**Solutions:**

1. **Fix permissions on the PDFs directory:**
   ```bash
   chmod -R 755 public/pdfs/
   ```

2. **Verify files are readable:**
   ```bash
   docker compose exec php ls -la public/pdfs/
   ```

   Files should show permissions like `-rwxr-xr-x` (755).

3. **Use the fix-permissions script:**
   ```bash
   ./bin/fix-permissions.sh
   ```

4. **Re-index after fixing permissions:**
   ```bash
   docker compose exec php php bin/console app:index-pdfs
   ```

**Prevention**: Always run `chmod -R 755 public/pdfs/` after copying new PDF files to the directory.

---

## Search Issues

### No Search Results

**Problem**: All searches return zero results.

**Solutions:**

1. **Verify Elasticsearch is running:**
   ```bash
   docker compose ps elasticsearch
   curl http://localhost:9200/_cluster/health
   ```

2. **Check if PDFs are indexed:**
   ```bash
   curl http://localhost:9200/pdf_pages/_count
   ```

3. **Re-index PDFs:**
   ```bash
   # Make sure permissions are correct first
   chmod -R 755 public/pdfs/
   docker compose exec php php bin/console app:index-pdfs
   ```

### Slow Search Performance

**Problem**: Searches take >1 second to complete.

**Solutions:**

1. **Check Elasticsearch cluster health:**
   ```bash
   curl http://localhost:9200/_cluster/health?pretty
   ```

2. **Verify vector search is enabled** (for Hybrid AI):
   ```bash
   curl http://localhost:9200/pdf_pages/_mapping | grep dense_vector
   ```

3. **Increase Elasticsearch memory:**
   ```yaml
   # docker-compose.yml
   elasticsearch:
     environment:
       - "ES_JAVA_OPTS=-Xms1g -Xmx1g"
   ```

### Semantic Search Not Working

**Problem**: Hybrid AI strategy returns same results as Exact match.

**Solutions:**

1. **Verify Ollama is running:**
   ```bash
   docker compose ps ollama
   curl http://localhost:11434/api/tags
   ```

2. **Check if embeddings model is installed:**
   ```bash
   docker compose exec ollama ollama list | grep nomic-embed-text
   ```

3. **Re-index with embeddings:**
   ```bash
   docker compose exec php php bin/console app:index-pdfs --skip-embeddings=false
   ```

---

## Translation Issues

### Translation Jobs Stuck in "Processing"

**Problem**: Translation jobs never complete.

**Solutions:**

1. **Check Messenger workers:**
   ```bash
   docker compose exec php ps aux | grep messenger
   ```

   Should see 3 workers running.

2. **Check worker logs:**
   ```bash
   ./bin/worker-logs.sh -f
   ```

3. **Restart workers:**
   ```bash
   docker compose restart php
   ```

### Translation Fails with "Model Not Found"

**Problem**: Translation fails with Ollama error.

**Solutions:**

1. **Verify qwen2.5 model is installed:**
   ```bash
   docker compose exec ollama ollama list | grep qwen2.5
   ```

2. **Download the model:**
   ```bash
   docker compose exec ollama ollama pull qwen2.5:7b
   ```

3. **Test Ollama directly:**
   ```bash
   curl http://localhost:11434/api/generate -d '{
     "model": "qwen2.5:7b",
     "prompt": "Hello",
     "stream": false
   }'
   ```

---

---

## Production vs Development Mode Issues

### Running Wrong Docker Mode

**Problem**: You're accidentally using production Docker setup for local development.

**Symptoms:**
- Files you add to `public/pdfs/` aren't visible inside containers
- Code changes don't take effect without rebuild
- Need to rebuild image after every change

**Diagnostic:**
```bash
docker compose config | grep -A2 "volumes:"
```

**If you see named volumes** (`app_public:/var/www/html/public`) instead of bind mounts (`. :/var/www/html`), you're in production mode.

**Solution:**

```bash
# Stop production containers
docker compose down

# Start in development mode (auto-loads docker-compose.override.yml)
./docker-dev.sh up

# Or manually:
docker compose up -d

# Verify you're now in development mode
docker compose config | grep -A2 "volumes:"
# Should show: - .:/var/www/html
```

**Prevent this issue:**
- Always use `./docker-dev.sh up` for local development
- Never use `docker compose -f docker-compose.yml up` for development
- See [docker.md](docker.md#volume-strategy-development-vs-production) for detailed explanation

---

## Docker Issues

### Containers Won't Start

**Problem**: `docker compose up` fails.

**Solutions:**

1. **Check for port conflicts:**
   ```bash
   lsof -i :80    # Apache
   lsof -i :5432  # PostgreSQL
   lsof -i :9200  # Elasticsearch
   ```

2. **Remove old containers and volumes:**
   ```bash
   docker compose down -v
   docker compose up -d
   ```

3. **Check Docker logs:**
   ```bash
   docker compose logs --tail=50
   ```

### Out of Disk Space

**Problem**: Docker runs out of space.

**Solutions:**

1. **Check Docker disk usage:**
   ```bash
   docker system df
   ```

2. **Clean up unused resources:**
   ```bash
   docker system prune -a --volumes
   ```

3. **Increase Docker disk allocation** (Docker Desktop → Settings → Resources)

---

## Database Issues

### Migration Fails

**Problem**: `php bin/console doctrine:migrations:migrate` fails.

**Solutions:**

1. **Check database connection:**
   ```bash
   docker compose exec php php bin/console dbal:run-sql "SELECT 1"
   ```

2. **Reset database:**
   ```bash
   docker compose exec php php bin/console doctrine:database:drop --force
   docker compose exec php php bin/console doctrine:database:create
   docker compose exec php php bin/console doctrine:migrations:migrate -n
   ```

3. **Check migration files for syntax errors:**
   ```bash
   docker compose exec php php -l migrations/*.php
   ```

---

## Frontend Issues

### Webpack Build Fails

**Problem**: `npm run build` fails with errors.

**Solutions:**

1. **Clear npm cache:**
   ```bash
   docker compose exec php npm cache clean --force
   docker compose exec php rm -rf node_modules package-lock.json
   docker compose exec php npm install
   ```

2. **Check Node.js version:**
   ```bash
   docker compose exec php node --version
   ```

   Should be v18 or higher.

3. **Rebuild with verbose output:**
   ```bash
   docker compose exec php npm run build -- --verbose
   ```

### Assets Not Loading

**Problem**: CSS/JS files return 404 errors.

**Solutions:**

1. **Verify assets were built:**
   ```bash
   ls -la public/build/
   ```

2. **Clear Symfony cache:**
   ```bash
   docker compose exec php php bin/console cache:clear
   ```

3. **Check Apache configuration** (assets should be served from `/build/`)

---

## Performance Optimization

### High Memory Usage

**Problem**: PHP container uses >2GB RAM.

**Solutions:**

1. **Limit PHP memory:**
   ```ini
   # .docker/dev/php/php.ini
   memory_limit = 512M
   ```

2. **Reduce Messenger worker count:**
   ```yaml
   # .docker/dev/supervisor/messenger-worker.conf
   numprocs=1
   ```

3. **Enable OPcache:**
   ```ini
   # .docker/dev/php/php.ini
   opcache.enable=1
   opcache.memory_consumption=256
   ```

### Elasticsearch High CPU

**Problem**: Elasticsearch uses >80% CPU constantly.

**Solutions:**

1. **Reduce index refresh interval:**
   ```bash
   curl -X PUT "localhost:9200/pdf_pages/_settings" -H 'Content-Type: application/json' -d'
   {
     "index": {
       "refresh_interval": "30s"
     }
   }'
   ```

2. **Disable unnecessary features:**
   ```bash
   # Disable slow log
   curl -X PUT "localhost:9200/pdf_pages/_settings" -d '{"index.search.slowlog.threshold.query.warn":"10s"}'
   ```

3. **Increase heap size:**
   ```yaml
   # docker-compose.yml
   ES_JAVA_OPTS: "-Xms2g -Xmx2g"
   ```

---

## Getting Help

If the issue persists:

1. **Check logs:**
   ```bash
   docker compose logs --tail=100 php
   docker compose logs --tail=100 elasticsearch
   docker compose logs --tail=100 ollama
   ```

2. **Enable debug mode:**
   ```env
   # .env
   APP_ENV=dev
   APP_DEBUG=1
   ```

3. **Report issue:** [GitHub Issues](https://github.com/josego85/pdf-content-search/issues)
   - Include error messages
   - Include relevant logs
   - Include steps to reproduce

---

**Related Documentation:**
- [Setup Guide](setup.md) - Installation steps
- [Docker Guide](docker.md) - Docker configuration
- [Testing Guide](testing.md) - Running tests
