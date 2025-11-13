# Docker Setup

## Quick Start

### Development (Default - Optimized)
```bash
# Recommended: Use optimized build script
./docker-build.sh

# Or standard build
docker-compose build
docker-compose up -d
```

**Performance:** First build ~2min, rebuilds ~5-15s (24x faster!)

### Production
```bash
docker-compose -f docker-compose.yml build --no-cache
docker-compose -f docker-compose.yml up -d
```

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
- **Kibana**: http://localhost:5601
- **PostgreSQL**: localhost:5432

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

## Common Commands

```bash
# Development
docker-compose build                    # Build development image
docker-compose up -d                    # Start development
docker-compose down                     # Stop services
docker-compose restart                  # Restart services
docker-compose logs -f                  # View all logs
docker-compose logs -f php              # View PHP logs
docker-compose exec php bash            # Access PHP container
docker-compose exec php npm run build   # Compile frontend

# Production
docker-compose -f docker-compose.yml build --no-cache   # Build production
docker-compose -f docker-compose.yml up -d              # Start production
docker-compose -f docker-compose.yml down               # Stop production

# Utilities
docker images | grep pdf-content-search    # Show image sizes
docker-compose ps                          # Show container status
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
docker-compose logs -f kibana
docker-compose logs -f database
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
