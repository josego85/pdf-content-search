#!/bin/bash
# ============================================
# View Messenger Worker Logs
# Senior Dev Helper Script
# ============================================

set -e

echo "📋 Messenger Worker Logs"
echo "========================"
echo ""

# Check if container is running
if ! docker compose ps php | grep -q "Up"; then
    echo "❌ PHP container is not running"
    exit 1
fi

# Show worker processes
echo "🔍 Worker Processes:"
docker compose exec php ps aux | grep "messenger:consume" | grep -v grep || echo "No workers running"

echo ""
echo "📝 Worker Logs (last 50 lines):"
echo "================================"

# Follow logs if -f flag is provided
if [ "$1" = "-f" ] || [ "$1" = "--follow" ]; then
    # Try to find log files
    if docker compose exec php test -f /var/log/supervisor/messenger-worker-00-stdout.log 2>/dev/null; then
        docker compose exec php tail -f /var/log/supervisor/messenger-worker-*.log
    elif docker compose exec php test -f /var/log/supervisor/messenger-worker.log 2>/dev/null; then
        docker compose exec php tail -f /var/log/supervisor/messenger-worker.log
    else
        echo "⚠️  Log files not found. Showing docker logs instead:"
        docker compose logs -f php | grep -i "messenger\|translation"
    fi
else
    # Try to find log files
    if docker compose exec php test -f /var/log/supervisor/messenger-worker-00-stdout.log 2>/dev/null; then
        docker compose exec php tail -n 50 /var/log/supervisor/messenger-worker-*.log
    elif docker compose exec php test -f /var/log/supervisor/messenger-worker.log 2>/dev/null; then
        docker compose exec php tail -n 50 /var/log/supervisor/messenger-worker.log
    else
        echo "⚠️  Log files not found. Showing docker logs instead:"
        docker compose logs --tail=50 php | grep -i "messenger\|translation"
    fi
fi
