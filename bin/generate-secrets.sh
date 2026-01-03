#!/bin/bash
# ============================================
# Generate Secure Secrets for Production
# ============================================

set -e

echo "========================================="
echo " PDF Content Search - Secret Generator"
echo "========================================="
echo ""
echo "Generating cryptographically secure secrets..."
echo ""

echo "# ========================================"
echo "# Generated Secrets - $(date)"
echo "# ========================================"
echo "# Add these to .env.production"
echo "# NEVER commit .env.production to Git!"
echo "# ========================================"
echo ""

echo "# Application secret (Symfony)"
echo "APP_SECRET=$(openssl rand -hex 32)"
echo ""

echo "# PostgreSQL database password (hex format - URL-safe)"
echo "POSTGRES_PASSWORD=$(openssl rand -hex 32)"
echo ""

echo "# Elasticsearch password (hex format - URL-safe)"
echo "ELASTIC_PASSWORD=$(openssl rand -hex 32)"
echo ""

echo "# ========================================"
echo "# Security Reminders"
echo "# ========================================"
echo "# ⚠️  Store these in a password manager"
echo "# ⚠️  Ensure .env.production is in .gitignore"
echo "# ⚠️  Rotate secrets every 90 days"
echo "# ⚠️  Use different secrets for dev/staging/prod"
echo ""
