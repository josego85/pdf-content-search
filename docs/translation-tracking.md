# Translation Job Tracking System

## Overview

The translation job tracking system provides complete visibility into the lifecycle of translation jobs from queue to completion. This addresses the monitoring blind spot where jobs being processed by Ollama (60-80 seconds) were not visible in standard Symfony Messenger stats.

## Job Lifecycle

Translation jobs go through the following states:

```
queued → processing → completed/failed
```

- **queued**: Job created and waiting for a worker to pick it up
- **processing**: Worker is actively translating with Ollama
- **completed**: Translation finished successfully
- **failed**: Translation encountered an error

## Components

### 1. TranslationJob Entity

Located in: [src/Entity/TranslationJob.php](src/Entity/TranslationJob.php)

Tracks each translation job with:
- PDF filename, page number, target language
- Status (queued/processing/completed/failed)
- Timestamps (created, started, completed)
- Worker PID for the processing worker
- Error message if failed

### 2. TranslationJobRepository

Located in: [src/Repository/TranslationJobRepository.php](src/Repository/TranslationJobRepository.php)

Provides query methods:
- `findActiveJobs()`: Get all queued/processing jobs
- `findExistingJob()`: Check for duplicate job
- `cleanupOldJobs()`: Remove old completed/failed jobs

### 3. MessageHandler Integration

Located in: [src/MessageHandler/TranslatePageMessageHandler.php](src/MessageHandler/TranslatePageMessageHandler.php)

Updates job status during processing:
- Finds or creates job record
- Marks as processing with worker PID: `$job->markAsProcessing(getmypid())`
- Marks as completed on success: `$job->markAsCompleted()`
- Marks as failed on exception: `$job->markAsFailed($error)`

### 4. Controller Integration

Located in: [src/Controller/PdfController.php](src/Controller/PdfController.php)

Creates initial job record when queueing translation:
```php
$job = new TranslationJob();
$job->setPdfFilename($pdfFilename);
$job->setPageNumber((int) $pageNumber);
$job->setTargetLanguage($targetLanguage);
$this->entityManager->persist($job);
$this->entityManager->flush();
```

## Monitoring Commands

### Console Command

View translation jobs using Symfony console:

```bash
# Show active jobs only (queued/processing)
docker compose exec php php bin/console app:translation:monitor

# Show all jobs including completed/failed
docker compose exec php php bin/console app:translation:monitor --all

# Watch mode - continuously refresh every 2 seconds
docker compose exec php php bin/console app:translation:monitor --watch
```

### Helper Script

Easier access via shell script:

```bash
# Show active jobs only
./bin/monitor-jobs.sh

# Show all jobs
./bin/monitor-jobs.sh --all

# Watch mode for real-time monitoring
./bin/monitor-jobs.sh --watch

# Help
./bin/monitor-jobs.sh --help
```

## Output Example

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

## Benefits

### 1. Real-time Visibility
- See jobs actively being processed by Ollama
- Track how long each translation has been running
- Identify which worker is handling each job

### 2. Better Debugging
- Worker PID helps correlate with logs
- Error messages captured for failed jobs
- Duration tracking helps identify slow translations

### 3. Monitoring During Long Translations
- Ollama translations take 60-80 seconds per page
- Jobs are now visible during entire processing period
- No more "blind spot" in monitoring

### 4. Duplicate Prevention
- Check for existing jobs before creating new ones
- Prevents queueing same translation multiple times

## Database Schema

Table: `translation_jobs`

```sql
CREATE TABLE translation_jobs (
    id SERIAL PRIMARY KEY,
    pdf_filename VARCHAR(255) NOT NULL,
    page_number INT NOT NULL,
    target_language VARCHAR(10) NOT NULL,
    status VARCHAR(20) NOT NULL,
    created_at TIMESTAMP NOT NULL,
    started_at TIMESTAMP,
    completed_at TIMESTAMP,
    error_message TEXT,
    worker_pid INT
);

CREATE INDEX idx_status ON translation_jobs (status);
CREATE INDEX idx_lookup ON translation_jobs (pdf_filename, page_number, target_language);
```

## Maintenance

### Cleanup Old Jobs

Jobs remain in the database indefinitely. You can clean up old completed/failed jobs programmatically:

```php
// In a custom command or scheduled task
$jobRepository->cleanupOldJobs(24);  // Older than 24 hours
```

Or directly via SQL:

```bash
docker compose exec database psql -U app -d app -c \
  "DELETE FROM translation_jobs
   WHERE status IN ('completed', 'failed')
   AND completed_at < NOW() - INTERVAL '24 hours';"
```

## Troubleshooting

### Jobs Stuck in "processing"

If jobs are stuck in "processing" status:

1. Check if workers are running:
   ```bash
   docker compose exec php supervisorctl status
   ```

2. Check worker logs:
   ```bash
   ./bin/worker-logs.sh
   ```

3. Look for the worker PID in logs:
   ```bash
   docker compose exec php ps aux | grep <worker_pid>
   ```

4. If worker crashed, job will remain in "processing". Manually update:
   ```sql
   UPDATE translation_jobs
   SET status = 'failed',
       error_message = 'Worker crashed'
   WHERE status = 'processing'
   AND started_at < NOW() - INTERVAL '10 minutes';
   ```

### High Number of Failed Jobs

Check error messages to identify patterns:

```bash
docker compose exec php php bin/console app:translation:monitor --all
```

Common causes:
- Ollama service down
- Memory limit exceeded
- Invalid PDF content
- Network timeouts

## Why not use messenger:stats?

Symfony's built-in `messenger:stats` only shows messages WAITING in the queue (not yet picked up by workers). Once a worker takes a message, it disappears from the queue immediately.

**The problem:** Ollama translations take 60-80 seconds, but `messenger:stats` shows nothing during that time.

**The solution:** `app:translation:monitor` tracks the complete lifecycle in a dedicated table.

```bash
# ❌ Limited visibility - only shows messages in queue
docker compose exec php php bin/console messenger:stats

# ✅ Complete visibility - shows entire translation lifecycle
./bin/monitor-jobs.sh --watch
```

**Key differences:**

| Feature | messenger:stats | app:translation:monitor |
|---------|----------------|------------------------|
| Shows queued messages | ✅ Yes | ✅ Yes |
| Shows active processing | ❌ No | ✅ Yes (60-80s of Ollama work) |
| Shows real duration | ❌ No | ✅ Yes (actual processing time) |
| Shows worker PID | ❌ No | ✅ Yes |
| Shows completed jobs | ❌ No | ✅ Yes |
| Shows errors | ❌ No | ✅ Yes |

## Related Documentation

- [Messenger Worker Setup](messenger-worker.md)
- [Frontend Architecture](frontend.md)
