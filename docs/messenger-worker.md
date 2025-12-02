# Messenger Worker Setup - Senior Dev Solution

## Overview

The **Messenger Worker** processes async translation jobs from the queue. Without it, translations get queued but **never execute**.

## Problem We Solved

**Before**: Messages stuck in queue for hours (58313+ seconds)
```
User requests translation → Message queued → ❌ NO WORKER → Never processes
```

**After**: Messages process automatically
```
User requests translation → Message queued → ✅ WORKER → Processed in seconds
```

## Solution: Supervisor (Single Container)

Instead of creating a separate container, we use **Supervisor** to run 2 processes in the PHP container:

1. **PHP-FPM** - Handles web requests
2. **Messenger Worker** - Processes async queue

### Why This Approach?

✅ **No extra container** - Uses existing PHP container
✅ **Auto-start** - Worker starts when container starts
✅ **Auto-restart** - Worker restarts if it crashes
✅ **Memory-safe** - Restarts every hour to prevent leaks
✅ **Logs separated** - Easy to debug

### Architecture

```
┌─────────────────────────────────┐
│   PHP Container (Alpine)        │
│                                 │
│  ┌──────────────────────────┐  │
│  │   Supervisor (Manager)    │  │
│  └───────┬─────────┬─────────┘  │
│          │         │             │
│    ┌─────▼────┐  ┌─▼──────────┐ │
│    │ PHP-FPM  │  │  Messenger  │ │
│    │  :9000   │  │   Worker    │ │
│    └──────────┘  └─────────────┘ │
└─────────────────────────────────┘
```

## Files Added

1. **`.docker/dev/supervisor/supervisord.conf`** - Supervisor configuration
2. **`bin/worker-logs.sh`** - Helper script to view logs

## Usage

### Rebuild Container (One-time Setup)

```bash
# Rebuild with Supervisor
docker compose build php

# Restart container
docker compose up -d
```

### Verify Worker is Running

```bash
# Check supervisor status
docker compose exec php supervisorctl status

# Should show:
# php-fpm              RUNNING   pid 12, uptime 0:05:23
# messenger-worker     RUNNING   pid 13, uptime 0:05:23
```

### View Worker Logs

```bash
# Last 50 lines
./bin/worker-logs.sh

# Follow logs (real-time)
./bin/worker-logs.sh -f
```

### Manual Control (if needed)

```bash
# Restart worker
docker compose exec php supervisorctl restart messenger-worker

# Stop worker
docker compose exec php supervisorctl stop messenger-worker

# Start worker
docker compose exec php supervisorctl start messenger-worker

# View all processes
docker compose exec php supervisorctl status
```

## Configuration

### Worker Settings (`supervisord.conf`)

```ini
[program:messenger-worker]
command=php /var/www/html/bin/console messenger:consume async --time-limit=3600 --memory-limit=128M -vv
user=www-data
numprocs=1  # Number of workers (increase for more parallelism)
autostart=true
autorestart=true
```

**Key Parameters**:
- `--time-limit=3600` - Worker restarts every hour (prevents memory leaks)
- `--memory-limit=128M` - Kills worker if exceeds 128MB RAM
- `-vv` - Verbose logging (helpful for debugging)

### Scaling Workers

To process more messages in parallel, edit `.docker/dev/supervisor/supervisord.conf`:

```ini
[program:messenger-worker]
numprocs=3  # Run 3 workers in parallel
process_name=%(program_name)s_%(process_num)02d
```

Then rebuild:
```bash
docker compose build php && docker compose up -d
```

## Monitoring

### Queue Statistics

```bash
# View queue stats
docker compose exec php php bin/console messenger:stats

# View failed messages
docker compose exec php php bin/console messenger:failed:show
```

### Worker Health Check

```bash
# Check if worker is consuming messages
docker compose exec php supervisorctl tail -f messenger-worker
```

### Logs Location

Inside container:
- **Worker logs**: `/var/log/supervisor/messenger-worker.log`
- **Worker errors**: `/var/log/supervisor/messenger-worker-error.log`
- **Supervisor logs**: `/var/log/supervisor/supervisord.log`

## Troubleshooting

### Problem: Worker not processing messages

```bash
# 1. Check supervisor status
docker compose exec php supervisorctl status

# 2. If stopped, restart
docker compose exec php supervisorctl restart messenger-worker

# 3. View logs
./bin/worker-logs.sh
```

### Problem: Messages still accumulating

```bash
# Check if worker is actually running
docker compose exec php ps aux | grep messenger:consume

# Should show:
# www-data  13  0.0  0.2  php bin/console messenger:consume async
```

### Problem: Worker crashes repeatedly

```bash
# View error logs
docker compose exec php tail -f /var/log/supervisor/messenger-worker-error.log

# Common issues:
# - Ollama not running → docker compose up -d ollama
# - Database connection → check DATABASE_URL in .env
# - Memory limit → increase in supervisord.conf
```

### Problem: Can't rebuild container

```bash
# Remove old container first
docker compose down
docker compose build --no-cache php
docker compose up -d
```

## Alternative: Manual Worker (Temporary)

If you don't want Supervisor, run worker manually:

```bash
# Start worker in background
docker compose exec -d php php bin/console messenger:consume async -vv

# Stop worker
docker compose exec php pkill -f "messenger:consume"
```

**Warning**: Manual worker stops when container restarts.

## Production Recommendations

For production, consider:

1. **Multiple workers** - Set `numprocs=3` or more
2. **Dedicated server** - Use separate server for workers
3. **Monitoring** - Use tools like Prometheus + Grafana
4. **Failure alerts** - Configure Supervisor to send alerts
5. **Log rotation** - Rotate logs daily to prevent disk filling

## Comparison: Before vs After

| Aspect | Before (Manual) | After (Supervisor) |
|--------|----------------|-------------------|
| **Auto-start** | ❌ No | ✅ Yes |
| **Auto-restart** | ❌ No | ✅ Yes |
| **Survives container restart** | ❌ No | ✅ Yes |
| **Logs** | ⚠️ Mixed with PHP | ✅ Separated |
| **Control** | ⚠️ Manual | ✅ Automated |
| **Extra containers** | ✅ 0 | ✅ 0 |

## Summary

✅ Worker runs automatically when container starts
✅ Restarts if crashes
✅ Memory-safe (restarts hourly)
✅ Easy to monitor (`./bin/worker-logs.sh`)
✅ No extra containers needed

This is the **senior dev professional** solution for single-container setups.
