# PDF Translation with Ollama

Complete guide to AI-powered PDF page translation using Ollama (qwen2.5:7b).

## Overview

The translation feature allows translating individual PDF pages to different languages using Ollama's local AI models. Translations are processed asynchronously via Symfony Messenger with full job tracking.

**Key Features:**
- ✅ **Async processing** - No blocking UI during translation (60-80s per page)
- ✅ **Job tracking** - Real-time visibility into translation status
- ✅ **3 Workers** - Parallel processing via Supervisor
- ✅ **Memory-safe** - Workers restart hourly to prevent leaks
- ✅ **Error handling** - Failed jobs captured with error messages

## How It Works

```
User clicks "Translate" → Job queued → Worker picks job → Ollama translates → Result stored
                         (instant)      (< 1s)          (60-80s)        (complete)
```

### Architecture

```
┌─────────────────────────────────────────────────────────┐
│   PHP Container (Alpine)                                │
│                                                          │
│  ┌──────────────────────────────────────────────────┐  │
│  │   Supervisor (Process Manager)                   │  │
│  └───────┬──────────┬──────────┬──────────┬─────────┘  │
│          │          │          │          │             │
│    ┌─────▼────┐  ┌──▼────┐  ┌─▼────┐  ┌─▼────┐       │
│    │ PHP-FPM  │  │Worker │  │Worker│  │Worker│       │
│    │  :9000   │  │  #1   │  │  #2  │  │  #3  │       │
│    └──────────┘  └───┬───┘  └──┬───┘  └──┬───┘       │
│                      │         │         │             │
└──────────────────────┼─────────┼─────────┼─────────────┘
                       │         │         │
                       └─────────┴─────────┴──── → Ollama (qwen2.5:7b)
```

## Job Lifecycle

Translation jobs go through these states:

```
queued → processing → completed/failed
```

- **queued**: Job created, waiting for worker
- **processing**: Worker actively translating with Ollama (60-80s)
- **completed**: Translation finished successfully
- **failed**: Translation encountered an error

## Translation Workflow

### 1. User Initiates Translation

From PDF viewer:
```
Click "Translate to Spanish" → POST /pdf/{filename}/{page}/translate
```

### 2. Job Created & Queued

Controller creates `TranslationJob` entity:
```php
$job = new TranslationJob();
$job->setPdfFilename($pdfFilename);
$job->setPageNumber((int) $pageNumber);
$job->setTargetLanguage($targetLanguage);
$entityManager->persist($job);
$entityManager->flush();

// Dispatch async message
$messageBus->dispatch(new TranslatePageMessage(...));
```

### 3. Worker Picks Job

Messenger worker (managed by Supervisor):
```bash
php bin/console messenger:consume async --time-limit=3600 --memory-limit=256M
```

Updates job status:
```php
$job->markAsProcessing(getmypid());  // Set worker PID
```

### 4. Ollama Translation

Worker calls Ollama API:
```php
$response = $ollamaService->generateChat([
    'model' => 'qwen2.5:7b',
    'messages' => [
        ['role' => 'system', 'content' => 'Translate to ' . $language],
        ['role' => 'user', 'content' => $pdfText]
    ],
    'options' => ['temperature' => 0.3]
]);
```

**Timeout:** 300s (5 minutes) for CPU processing

### 5. Result Stored

On success:
```php
$job->markAsCompleted();
$cache->set($cacheKey, $translatedText, 3600);
```

On failure:
```php
$job->markAsFailed($exception->getMessage());
```

## Monitoring

### Real-Time Job Tracking

**Console command:**
```bash
# Active jobs only (queued/processing)
docker compose -p pdf-content-search exec php php bin/console app:translation:monitor

# All jobs including completed/failed
docker compose -p pdf-content-search exec php php bin/console app:translation:monitor --all

# Watch mode - refresh every 2 seconds
docker compose -p pdf-content-search exec php php bin/console app:translation:monitor --watch
```

**Helper script:**
```bash
# Active jobs
./bin/monitor-jobs.sh

# All jobs
./bin/monitor-jobs.sh --all

# Watch mode
./bin/monitor-jobs.sh --watch
```

**Output example:**
```
Translation Jobs Monitor
========================

+----+-----------+--------------------------------+------+------+------------+---------+---------+----------+-------+
| ID | Status    | PDF                            | Page | Lang | Worker PID | Created | Started | Duration | Error |
+----+-----------+--------------------------------+------+------+------------+---------+---------+----------+-------+
| 5  | processing| ...nd on-prem environments.pdf | 100  | es   | 12         | 2s ago  | 1s ago  | 1m 15s   | -     |
| 4  | queued    | ...nd on-prem environments.pdf | 101  | es   | -          | 1s ago  | -       | -        | -     |
| 3  | completed | ...nd on-prem environments.pdf | 504  | es   | 9          | 20h ago | 20h ago | 54s      | -     |
+----+-----------+--------------------------------+------+------+------------+---------+---------+----------+-------+

Summary
-------
 Queued: 1 | Processing: 1 | Completed: 1 | Failed: 0
```

### Worker Status

```bash
# Check supervisor status
docker compose -p pdf-content-search exec php supervisorctl status

# Expected output:
# php-fpm                          RUNNING   pid 12, uptime 0:05:23
# messenger-worker_00              RUNNING   pid 13, uptime 0:05:23
# messenger-worker_01              RUNNING   pid 14, uptime 0:05:23
# messenger-worker_02              RUNNING   pid 15, uptime 0:05:23
```

### Worker Logs

```bash
# Last 50 lines
./bin/worker-logs.sh

# Follow logs (real-time)
./bin/worker-logs.sh -f

# Specific worker
docker compose -p pdf-content-search exec php \
  tail -f /var/log/supervisor/messenger-worker-00.log
```

### Queue Statistics

```bash
# View queue stats
docker compose -p pdf-content-search exec php \
  php bin/console messenger:stats

# View failed messages
docker compose -p pdf-content-search exec php \
  php bin/console messenger:failed:show

# Retry failed messages
docker compose -p pdf-content-search exec php \
  php bin/console messenger:failed:retry
```

## Configuration

### Worker Settings

**Development:** `.docker/dev/supervisor/supervisord.conf`
```ini
[program:messenger-worker]
command=php /var/www/html/bin/console messenger:consume async --time-limit=3600 --memory-limit=256M -vv
user=www-data
numprocs=3
process_name=%(program_name)s_%(process_num)02d
autostart=true
autorestart=true
priority=10
```

**Production:** `.docker/prod/supervisor/supervisord.conf`
```ini
[program:messenger-worker]
command=php /var/www/html/bin/console messenger:consume async --time-limit=3600 --memory-limit=512M -vv
user=www-data
numprocs=3
```

**Key parameters:**
- `--time-limit=3600` - Worker restarts every hour (prevents memory leaks)
- `--memory-limit=256M` - Kills worker if exceeds memory
- `-vv` - Verbose logging
- `numprocs=3` - Number of parallel workers

### Scaling Workers

To process more translations in parallel, increase `numprocs`:

```ini
# Edit .docker/dev/supervisor/supervisord.conf
numprocs=5  # Increase from 3 to 5
```

Rebuild:
```bash
make rebuild-dev
```

### Ollama Configuration

**Models:**
```env
# In .env
OLLAMA_MODEL=qwen2.5:7b              # Translation model (4.7GB)
OLLAMA_EMBEDDING_MODEL=nomic-embed-text  # Not used for translation
```

**Alternative models:**
```env
OLLAMA_MODEL=llama3.2:1b  # Smaller (1.3GB), faster, less accurate
```

**Timeout:**
```php
// src/Service/OllamaService.php
$this->client->request('POST', ..., [
    'timeout' => 300,  // 5 minutes for CPU processing
]);
```

## Database Schema

**Table:** `translation_jobs`

```sql
CREATE TABLE translation_jobs (
    id SERIAL PRIMARY KEY,
    pdf_filename VARCHAR(255) NOT NULL,
    page_number INT NOT NULL,
    target_language VARCHAR(10) NOT NULL,
    status VARCHAR(20) NOT NULL,  -- queued/processing/completed/failed
    created_at TIMESTAMP NOT NULL,
    started_at TIMESTAMP,
    completed_at TIMESTAMP,
    error_message TEXT,
    worker_pid INT
);

CREATE INDEX idx_status ON translation_jobs (status);
CREATE INDEX idx_lookup ON translation_jobs (pdf_filename, page_number, target_language);
```

## Troubleshooting

### Jobs Stuck in "processing"

If jobs remain in "processing" for > 10 minutes:

**1. Check if workers are running:**
```bash
docker compose -p pdf-content-search exec php supervisorctl status
```

**2. Check worker logs for errors:**
```bash
./bin/worker-logs.sh -f
```

**3. Check Ollama status:**
```bash
docker compose -p pdf-content-search logs ollama --tail=50
```

**4. Manually mark as failed if worker crashed:**
```bash
docker compose -p pdf-content-search exec database \
  psql -U app_user -d pdf_search -c \
  "UPDATE translation_jobs
   SET status = 'failed', error_message = 'Worker crashed'
   WHERE status = 'processing'
   AND started_at < NOW() - INTERVAL '10 minutes';"
```

### High Number of Failed Jobs

Check error messages:
```bash
docker compose -p pdf-content-search exec php \
  php bin/console app:translation:monitor --all | grep failed
```

**Common causes:**
- Ollama service down → `docker compose -p pdf-content-search restart ollama`
- Memory limit exceeded → Increase in `supervisord.conf`
- Network timeout → Increase timeout in `OllamaService.php`
- Invalid PDF content → Check PDF text extraction

### Workers Not Processing

**1. Verify Supervisor is running:**
```bash
docker compose -p pdf-content-search exec php ps aux | grep supervisor
```

**2. Restart workers:**
```bash
docker compose -p pdf-content-search exec php supervisorctl restart messenger-worker:*
```

**3. Rebuild container if Supervisor config changed:**
```bash
make rebuild-dev
```

### Slow Translations

**Normal:** 60-80 seconds per page with qwen2.5:7b on CPU

**Speed up:**
1. Use smaller model: `OLLAMA_MODEL=llama3.2:1b` (1.3GB, ~30s per page)
2. Add GPU support (if available)
3. Increase worker count (`numprocs=5`)

### Queue Not Consuming

**Check messenger transport:**
```bash
docker compose -p pdf-content-search exec php \
  php bin/console messenger:stats
```

**Ensure table exists:**
```bash
docker compose -p pdf-content-search exec php \
  php bin/console messenger:setup-transports
```

## Maintenance

### Cleanup Old Jobs

Jobs remain in database indefinitely. Clean up completed/failed jobs:

**Via command (future implementation):**
```bash
php bin/console app:translation:cleanup --days=7
```

**Via SQL:**
```bash
docker compose -p pdf-content-search exec database \
  psql -U app_user -d pdf_search -c \
  "DELETE FROM translation_jobs
   WHERE status IN ('completed', 'failed')
   AND completed_at < NOW() - INTERVAL '7 days';"
```

### Monitor Disk Usage

**Check database size:**
```bash
docker compose -p pdf-content-search exec database \
  psql -U app_user -d pdf_search -c \
  "SELECT pg_size_pretty(pg_database_size('pdf_search'));"
```

**Check Ollama models:**
```bash
docker compose -p pdf-content-search exec ollama ollama list
```

## Why Not Use messenger:stats?

Symfony's built-in `messenger:stats` only shows messages **waiting in queue** (not yet picked up). Once a worker takes a message, it disappears immediately.

**The problem:** Ollama translations take 60-80 seconds, but `messenger:stats` shows nothing during that time.

**The solution:** `app:translation:monitor` tracks complete lifecycle in dedicated table.

```bash
# ❌ Limited visibility - only queued messages
docker compose -p pdf-content-search exec php php bin/console messenger:stats

# ✅ Complete visibility - entire lifecycle
./bin/monitor-jobs.sh --watch
```

| Feature | messenger:stats | app:translation:monitor |
|---------|----------------|------------------------|
| Shows queued messages | ✅ Yes | ✅ Yes |
| Shows active processing | ❌ No | ✅ Yes (60-80s) |
| Shows duration | ❌ No | ✅ Yes |
| Shows worker PID | ❌ No | ✅ Yes |
| Shows completed jobs | ❌ No | ✅ Yes |
| Shows errors | ❌ No | ✅ Yes |

## Related Files

**Backend:**
- `src/Entity/TranslationJob.php` - Job entity
- `src/Repository/TranslationJobRepository.php` - Queries
- `src/MessageHandler/TranslatePageMessageHandler.php` - Worker logic
- `src/Controller/PdfController.php` - Translation endpoint
- `src/Command/TranslationMonitorCommand.php` - Monitoring command

**Config:**
- `.docker/dev/supervisor/supervisord.conf` - Worker setup (dev)
- `.docker/prod/supervisor/supervisord.conf` - Worker setup (prod)
- `config/packages/messenger.yaml` - Messenger transport

**Scripts:**
- `bin/monitor-jobs.sh` - Job monitoring helper
- `bin/worker-logs.sh` - Worker log viewer

## Next Steps

- [Configuration Guide](../configuration.md) - Environment variables
- [Production Guide](../production.md) - Deploy & scaling
- [Troubleshooting](../troubleshooting.md) - Common issues
