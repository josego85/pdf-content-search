# Docker Buildx Installation Guide (Optional)

## Why Install Buildx?

Buildx enables **COMPOSE_BAKE** for additional performance benefits:
- Parallel builds when building multiple services
- Better build dependency handling
- Enhanced caching strategies

**Note**: Our multi-stage BuildKit optimization already provides 24x faster builds. Buildx provides marginal additional improvements for multi-service builds.

## Current Performance (Without Buildx)

✅ **Already very fast with BuildKit**:
- First build: ~2min
- Rebuild (no changes): ~5s
- Rebuild (code changes): ~15s

## Installation

### Linux

#### Option 1: Docker Desktop (Easiest)

Install [Docker Desktop](https://docs.docker.com/desktop/install/linux-install/) which includes buildx by default.

#### Option 2: Manual Installation

```bash
# 1. Download latest buildx release
BUILDX_VERSION=$(curl -s https://api.github.com/repos/docker/buildx/releases/latest | grep '"tag_name"' | sed -E 's/.*"v([^"]+)".*/\1/')
wget https://github.com/docker/buildx/releases/download/v${BUILDX_VERSION}/buildx-v${BUILDX_VERSION}.linux-amd64

# 2. Make executable and move to plugins directory
mkdir -p ~/.docker/cli-plugins/
chmod +x buildx-v${BUILDX_VERSION}.linux-amd64
mv buildx-v${BUILDX_VERSION}.linux-amd64 ~/.docker/cli-plugins/docker-buildx

# 3. Verify installation
docker buildx version

# 4. Create and use builder instance
docker buildx create --name mybuilder --use
```

### Windows

Docker Desktop for Windows includes buildx by default.

## Verification

After installation:

```bash
# Check buildx is available
docker buildx version
# Output: github.com/docker/buildx v0.30.0 124418cd66f056c2b1111f173c3f48fa695a089e

# Test build with Bake enabled
./docker-build.sh

# Should show:
# - BuildKit: ✅ (cache mounts enabled)
# - Bake: ✅ (parallel builds enabled)  <-- This line confirms buildx works
```

## Uninstallation

If you want to remove buildx:

```bash
# Remove plugin
rm ~/.docker/cli-plugins/docker-buildx

# Remove builder instance
docker buildx rm mybuilder
```

## Troubleshooting

### "buildx not found" error

```bash
# Check if plugin exists
ls -la ~/.docker/cli-plugins/

# If missing, reinstall following steps above
```

### Permission denied

```bash
# Make plugin executable
chmod +x ~/.docker/cli-plugins/docker-buildx

# Or install system-wide (requires sudo)
sudo mv buildx-*.linux-amd64 /usr/local/lib/docker/cli-plugins/docker-buildx
```

### Builder instance issues

```bash
# Remove and recreate builder
docker buildx rm mybuilder
docker buildx create --name mybuilder --use --bootstrap
```

## Do I Need Buildx?

**TL;DR: Nice to have, but not required**

| Scenario | Buildx Benefit | Recommendation |
|----------|----------------|----------------|
| **Single service builds** (php only) | Minimal (~same speed) | ⚪ Optional |
| **Multi-service builds** (php + others) | Moderate improvement | 🟡 Recommended |
| **Complex multi-stage builds** | Good improvement | 🟢 Install it |
| **Already happy with 24x speedup** | Marginal | ⚪ Skip it |

## Performance Comparison

```bash
# Without Buildx (BuildKit only)
docker compose build
# Rebuild time: ~5-15s ✅ Already very fast!

# With Buildx (BuildKit + Bake)
COMPOSE_BAKE=true docker compose build
# Rebuild time: ~5-12s ✅ Slightly faster
```

**Verdict**: BuildKit alone provides 95% of the performance gains. Buildx adds the final 5%.

## Resources

- [Official Buildx Documentation](https://docs.docker.com/build/install-buildx/)
- [Buildx GitHub Repository](https://github.com/docker/buildx)
- [Docker BuildKit Documentation](https://docs.docker.com/build/buildkit/)
