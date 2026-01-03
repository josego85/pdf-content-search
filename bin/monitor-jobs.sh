#!/bin/bash
# ============================================
# Translation Jobs Monitor - Optimized
# ============================================
# Monitors Messenger queue and Supervisor workers
# ============================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
cd "$PROJECT_DIR" || exit 1

# Colors
BLUE='\033[0;34m'
GREEN='\033[0;32m'
CYAN='\033[0;36m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Docker Compose command (auto-detect project)
COMPOSE="docker compose -p pdf-content-search"

# Function to show status
show_status() {
    clear
    echo -e "${CYAN}╔════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║   Translation Jobs Monitor (Live)         ║${NC}"
    echo -e "${CYAN}╚════════════════════════════════════════════╝${NC}"
    echo ""

    # Check Messenger workers
    echo -e "${BLUE}📊 Messenger Workers:${NC}"
    WORKERS=$($COMPOSE exec -T php ps aux 2>/dev/null | grep "messenger:consume" | grep -v grep || true)

    if [ -z "$WORKERS" ]; then
        echo -e "${RED}❌ No workers running${NC}"
    else
        WORKER_COUNT=$(echo "$WORKERS" | wc -l)
        echo -e "${GREEN}✅ $WORKER_COUNT workers active${NC}"
        echo "$WORKERS" | awk 'NF > 0 {printf "  → PID %s (user: %s, uptime: %s)\n", $1, $2, $3}'
    fi
    echo ""

    # Check Messenger queue
    echo -e "${BLUE}📨 Messenger Queue:${NC}"
    QUEUE_SQL="SELECT
        queue_name,
        COUNT(*) as total,
        SUM(CASE WHEN delivered_at IS NULL THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN delivered_at IS NOT NULL THEN 1 ELSE 0 END) as processing,
        MAX(EXTRACT(EPOCH FROM (NOW() - created_at))::int) as oldest_seconds
    FROM messenger_messages
    GROUP BY queue_name;"

    QUEUE_DATA=$($COMPOSE exec -T php php bin/console dbal:run-sql "$QUEUE_SQL" --format csv 2>/dev/null | tail -n +2 | tr ',' '|' || true)

    if [ -z "$QUEUE_DATA" ]; then
        echo -e "${GREEN}✅ Queue empty${NC}"
    else
        echo "$QUEUE_DATA" | while IFS='|' read queue total pending processing oldest; do
            echo -e "${YELLOW}Queue: $queue${NC}"
            echo "  Total: $total | Pending: $pending | Processing: $processing"
            if [ "$oldest" != "" ] && [ "$oldest" -gt 60 ]; then
                echo -e "  ${RED}⚠️  Oldest job: ${oldest}s (stuck?)${NC}"
            elif [ "$oldest" != "" ]; then
                echo -e "  Oldest job: ${oldest}s"
            fi
        done
    fi
    echo ""

    # Recent translations
    echo -e "${BLUE}📝 Recent Translations (last 5):${NC}"
    TRANS_SQL="SELECT
        pdf_filename,
        page_number,
        target_language,
        EXTRACT(EPOCH FROM (NOW() - created_at))::int as seconds_ago
    FROM pdf_page_translations
    ORDER BY created_at DESC
    LIMIT 5;"

    TRANS_DATA=$($COMPOSE exec -T php php bin/console dbal:run-sql "$TRANS_SQL" --format csv 2>/dev/null | tail -n +2 | tr ',' '|' || true)

    if [ -z "$TRANS_DATA" ]; then
        echo -e "  ${YELLOW}No translations yet${NC}"
    else
        echo "$TRANS_DATA" | while IFS='|' read file page lang seconds; do
            [ -n "$file" ] && echo "  ${file} (p${page}) → ${lang} - ${seconds}s ago"
        done
    fi

    if [ "$1" == "watch" ]; then
        echo ""
        echo -e "${YELLOW}Refreshing every 2s... Press Ctrl+C to stop${NC}"
    fi
}

# Help
if [[ "$1" == "--help" ]] || [[ "$1" == "-h" ]]; then
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  -w, --watch     Watch mode - refresh every 2 seconds"
    echo "  -h, --help      Show this help"
    echo ""
    echo "Examples:"
    echo "  $0              # Show current status"
    echo "  $0 --watch      # Watch mode (live updates)"
    exit 0
fi

# Watch mode
if [[ "$1" == "--watch" ]] || [[ "$1" == "-w" ]]; then
    while true; do
        show_status watch
        sleep 2
    done
else
    show_status
fi
