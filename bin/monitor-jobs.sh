#!/bin/bash

# Translation Jobs Monitor Helper Script
# Provides easy access to translation job monitoring

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

cd "$PROJECT_DIR" || exit 1

# Colors
GREEN='\033[0;32m'
CYAN='\033[0;36m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${CYAN}Translation Jobs Monitor${NC}"
echo "========================"
echo ""

# Check if --watch or -w flag is provided
if [[ "$1" == "--watch" ]] || [[ "$1" == "-w" ]]; then
    echo -e "${YELLOW}Starting watch mode (refreshes every 2 seconds)${NC}"
    echo -e "${YELLOW}Press Ctrl+C to stop${NC}"
    echo ""
    docker compose exec php php bin/console app:translation:monitor --watch
elif [[ "$1" == "--all" ]] || [[ "$1" == "-a" ]]; then
    echo -e "${GREEN}Showing all jobs (including completed/failed)${NC}"
    echo ""
    docker compose exec php php bin/console app:translation:monitor --all
elif [[ "$1" == "--help" ]] || [[ "$1" == "-h" ]]; then
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  (no options)    Show active jobs (queued/processing)"
    echo "  -w, --watch     Watch mode - continuously refresh every 2 seconds"
    echo "  -a, --all       Show all jobs including completed/failed"
    echo "  -h, --help      Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0              # Show only active jobs"
    echo "  $0 --watch      # Watch mode for real-time monitoring"
    echo "  $0 --all        # Show all jobs"
else
    echo -e "${GREEN}Showing active jobs only (queued/processing)${NC}"
    echo ""
    docker compose exec php php bin/console app:translation:monitor
fi
