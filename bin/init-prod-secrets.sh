#!/bin/bash
# ============================================
# Initialize Production Secrets
# ============================================
# Generates .env.prod.local with secure secrets
# Usage: ./bin/init-prod-secrets.sh
# ============================================

set -e

OUTPUT_FILE=".env.prod.local"

if [ -f "$OUTPUT_FILE" ]; then
    echo "⚠️  $OUTPUT_FILE already exists!"
    read -p "Overwrite? (y/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Cancelled"
        exit 0
    fi
fi

echo "🔐 Generating production secrets..."
echo ""

# Generate secrets
APP_SECRET=$(openssl rand -hex 32)
POSTGRES_PASSWORD=$(openssl rand -hex 32)
ELASTIC_PASSWORD=$(openssl rand -hex 32)

# Write to file
cat > "$OUTPUT_FILE" << ENVFILE
# ============================================
# Production Secrets
# ============================================
# Generated: $(date)
# ⚠️  NEVER commit this file to Git!
# ============================================

###> symfony/framework-bundle ###
APP_SECRET=$APP_SECRET
###< symfony/framework-bundle ###

###> doctrine/doctrine-bundle ###
POSTGRES_PASSWORD=$POSTGRES_PASSWORD
###< doctrine/doctrine-bundle ###

###> Elasticsearch ###
ELASTIC_PASSWORD=$ELASTIC_PASSWORD
###< Elasticsearch ###
ENVFILE

chmod 600 "$OUTPUT_FILE"

echo "✅ Created $OUTPUT_FILE"
echo ""
echo "Secrets generated:"
echo "  APP_SECRET: ${APP_SECRET:0:16}..."
echo "  POSTGRES_PASSWORD: ${POSTGRES_PASSWORD:0:16}..."
echo "  ELASTIC_PASSWORD: ${ELASTIC_PASSWORD:0:16}..."
echo ""
echo "Next steps:"
echo "  1. Review $OUTPUT_FILE"
echo "  2. Run: make prod"
