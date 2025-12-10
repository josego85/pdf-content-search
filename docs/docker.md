# Docker Setup

## Quick Start

### Development (Recommended)
```bash
# Start with automatic migrations
./docker-dev.sh up

# Or manual start
docker compose up -d
```

**Ports:**
- Web: http://localhost
- Elasticsearch: http://localhost:9200
- PostgreSQL: localhost:5432
- Ollama: http://localhost:11435
- Analytics: http://localhost/analytics

### Production
```bash
# Start with automatic migrations
./docker-prod.sh up

# Access at http://localhost:8080
```

**Port:** http://localhost:8080

## Management Scripts

### Development: `docker-dev.sh`

```bash
./docker-dev.sh up       # Start + migrations
./docker-dev.sh down     # Stop (keeps data)
./docker-dev.sh restart  # Restart services
./docker-dev.sh logs     # View logs
./docker-dev.sh exec php bash  # Execute command
./docker-dev.sh build    # Rebuild images
./docker-dev.sh clean    # Remove volumes (⚠️ deletes data)
```

### Production: `docker-prod.sh`

```bash
./docker-prod.sh up       # Start + migrations
./docker-prod.sh down     # Stop (keeps data)
./docker-prod.sh restart  # Restart services
./docker-prod.sh logs     # View logs
./docker-prod.sh exec php bash  # Execute command
./docker-prod.sh build    # Rebuild images
./docker-prod.sh clean    # Remove volumes (⚠️ deletes data)
```

**Key Difference:** Dev and prod use **separate Docker projects** with isolated volumes and data.

## Architecture

### Image Sizes
- **Development**: 525MB (Alpine + Node.js + tools)
- **Production**: ~250MB (Alpine multi-stage, optimized)

### Structure
```
.docker/
├── dev/Dockerfile       # Development image (Node.js included)
└── prod/Dockerfile      # Production image (multi-stage build)

docker-compose.yml           # Production base
docker-compose.override.yml  # Development override (auto-loaded)
```

## Development

The `docker-compose.override.yml` is **automatically loaded** in development:

```bash
docker-compose up -d    # Auto-loads override
```

### Services Available
- **App**: http://localhost
- **Elasticsearch**: http://localhost:9200
- **PostgreSQL**: localhost:5432
- **Analytics Dashboard**: http://localhost/analytics

### Common Tasks

**Build frontend assets:**
```bash
docker-compose exec php npm run build
```

**Access container shell:**
```bash
docker-compose exec php bash
```

**View logs:**
```bash
docker-compose logs -f              # All services
docker-compose logs -f php          # PHP only
```

**Restart services:**
```bash
docker-compose restart
```

## Production

Production uses **only** `docker-compose.yml` (no override):

```bash
# Build optimized image (multi-stage)
docker-compose -f docker-compose.yml build --no-cache

# Start production services
docker-compose -f docker-compose.yml up -d

# Stop production services
docker-compose -f docker-compose.yml down
```

### Production Differences
- ✅ Alpine-based multi-stage build
- ✅ No Node.js in runtime
- ✅ Composer removed after install
- ✅ Read-only file system
- ✅ No debug tools
- ✅ Optimized autoloader
- ✅ Non-root user (www-data)
- ✅ No exposed ports (except HTTP)

## Environment Variables

Create `.env` file:

```env
# Database
POSTGRES_VERSION=16
POSTGRES_DB=app
POSTGRES_USER=app
POSTGRES_PASSWORD=!ChangeMe!

# Elasticsearch
ES_JAVA_OPTS=-Xms1g -Xmx1g

# App
APP_ENV=dev
HTTP_PORT=80
```

## Low-Level Commands

If you need direct Docker Compose access (not using the scripts):

```bash
# Development (auto-loads docker-compose.override.yml)
docker compose build                    # Build images
docker compose up -d                    # Start services
docker compose down                     # Stop services
docker compose logs -f php              # View logs
docker compose exec php bash            # Access container

# Production (isolated project)
docker compose -p pdf-search-prod -f docker-compose.yml build
docker compose -p pdf-search-prod -f docker-compose.yml up -d
docker compose -p pdf-search-prod -f docker-compose.yml down

# Utilities
docker images | grep pdf-content-search    # Show image sizes
docker compose ps                          # Show container status
docker system df                           # Check disk usage
```

## Troubleshooting

### Rebuild from scratch
```bash
docker-compose down -v
docker-compose build --no-cache
docker-compose up -d
```

### Check container status
```bash
docker-compose ps
```

### View specific service logs
```bash
docker-compose logs -f elasticsearch
docker-compose logs -f database
docker-compose logs -f php
```

### Clean orphan containers
```bash
docker-compose down --remove-orphans
```

### Check disk usage
```bash
docker system df
docker images | grep pdf-content-search
```

## Performance Tips

1. **Use optimized build script** (recommended):
   ```bash
   ./docker-build.sh  # Auto-enables BuildKit + cache optimizations
   ```
   - Rebuilds: ~5-15s (vs 2min+ without optimization)
   - Multi-stage builds with cached PHP extensions
   - BuildKit cache mounts for Composer/npm

2. **Optional: Install docker-buildx** for marginal additional speed:
   - Enables COMPOSE_BAKE for parallel multi-service builds
   - See installation guide: **[docker-buildx-install.md](docker-buildx-install.md)**
   - Note: Already 24x faster with BuildKit alone

3. **Adjust Elasticsearch memory** (`.env`):
   ```env
   ES_JAVA_OPTS=-Xms2g -Xmx2g  # For more memory
   ```

4. **Prune unused resources**:
   ```bash
   docker system prune -a
   ```

## Best Practices

✅ **Don't commit `.env`** - Contains secrets
✅ **Use named volumes** - Better performance than bind mounts in prod
✅ **Keep images small** - Faster deployments
✅ **Multi-stage builds** - Optimal production images
✅ **Health checks** - Ensure services are ready
✅ **Resource limits** - Prevent container from consuming all resources
