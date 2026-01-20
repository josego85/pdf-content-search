#!/bin/bash
# ============================================
# PHPUnit Coverage Helper Script
# Makes it easy to generate coverage reports
# ============================================

set -e

CONTAINER_NAME="pdf-content-search-php-1"

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}📊 PHPUnit Code Coverage Generator${NC}"
echo ""

# Check if Docker container is running
if ! docker ps | grep -q "$CONTAINER_NAME"; then
    echo -e "${YELLOW}⚠️  PHP container not running. Starting services...${NC}"
    docker compose up -d
    sleep 3
fi

# Check Xdebug is enabled
echo -e "${GREEN}🔍 Checking Xdebug installation...${NC}"
if docker compose exec php php -v | grep -i xdebug > /dev/null; then
    echo -e "${GREEN}✅ Xdebug is enabled${NC}"
else
    echo -e "${YELLOW}❌ Xdebug not found. Please rebuild Docker image:${NC}"
    echo "   docker compose build --no-cache php"
    exit 1
fi

# Generate coverage
echo ""
echo -e "${GREEN}🧪 Running tests with coverage...${NC}"
echo ""

# HTML report (human-readable)
docker compose exec php vendor/bin/phpunit --coverage-html coverage/

echo ""
echo -e "${GREEN}✅ Coverage report generated!${NC}"
echo ""
echo "📁 HTML Report: ./coverage/index.html"
echo "   Open in browser to view detailed coverage"
echo ""
echo "📊 Quick stats:"
docker compose exec php vendor/bin/phpunit --coverage-text | grep "Lines:"

# Optional: Clover XML for CI
if [ "$1" == "--xml" ]; then
    echo ""
    echo -e "${GREEN}📄 Generating XML report for CI...${NC}"
    docker compose exec php vendor/bin/phpunit --coverage-clover coverage.xml
    echo "✅ coverage.xml generated"
fi

echo ""
echo -e "${GREEN}🎯 Coverage threshold: 85%+${NC}"
