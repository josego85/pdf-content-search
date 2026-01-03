# REST API Documentation

Complete API reference for programmatic access to analytics and search features.

## Base URL

```
http://localhost
```

## Authentication

⚠️ **NOT IMPLEMENTED** - All endpoints are currently publicly accessible without authentication.

See [Security & Rate Limiting](#security--rate-limiting) section for production hardening recommendations.

## Analytics Endpoints

### Overview Metrics

Get aggregated metrics for a time period.

**Endpoint:**
```http
GET /api/analytics/overview?days={days}
```

**Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `days` | integer | No | 7 | Number of days to analyze (7, 14, 30, 90) |

**Response:**
```json
{
  "status": "success",
  "data": {
    "total_searches": 1234,
    "unique_sessions": 567,
    "avg_response_time_ms": 45,
    "zero_result_searches": 12,
    "click_through_rate": 78.5,
    "success_rate": 99.0,
    "period": {
      "start": "2025-12-11",
      "end": "2025-12-18",
      "days": 7
    }
  }
}
```

**Example:**
```bash
curl http://localhost/api/analytics/overview?days=30
```

---

### Top Search Queries

Get most popular search queries with engagement metrics.

**Endpoint:**
```http
GET /api/analytics/top-queries?days={days}&limit={limit}
```

**Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `days` | integer | No | 7 | Time range in days |
| `limit` | integer | No | 20 | Maximum number of results (1-100) |

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "query": "java programming",
      "search_count": 45,
      "avg_results": 12,
      "click_rate": 75
    },
    {
      "query": "python testing",
      "search_count": 38,
      "avg_results": 8,
      "click_rate": 42
    }
  ]
}
```

**Example:**
```bash
curl "http://localhost/api/analytics/top-queries?days=7&limit=10"
```

---

### Daily Search Trends

Get daily search volume broken down by strategy.

**Endpoint:**
```http
GET /api/analytics/trends?days={days}
```

**Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `days` | integer | No | 7 | Time range in days |

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "date": "2025-12-18",
      "total": 123,
      "by_strategy": {
        "hybrid_ai": 89,
        "exact": 23,
        "prefix": 11
      }
    },
    {
      "date": "2025-12-17",
      "total": 145,
      "by_strategy": {
        "hybrid_ai": 102,
        "exact": 28,
        "prefix": 15
      }
    }
  ]
}
```

**Example:**
```bash
curl http://localhost/api/analytics/trends?days=14
```

---

### Click Position Distribution

Analyze which result positions users click most frequently.

**Endpoint:**
```http
GET /api/analytics/click-positions?days={days}
```

**Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `days` | integer | No | 7 | Time range in days |

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "position": 1,
      "clicks": 456,
      "percentage": 45.6
    },
    {
      "position": 2,
      "clicks": 234,
      "percentage": 23.4
    },
    {
      "position": 3,
      "clicks": 123,
      "percentage": 12.3
    }
  ]
}
```

**Example:**
```bash
curl http://localhost/api/analytics/click-positions?days=7
```

---

### Zero-Result Queries

Identify searches that returned no results (content gaps).

**Endpoint:**
```http
GET /api/analytics/zero-results?days={days}&limit={limit}
```

**Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `days` | integer | No | 7 | Time range in days |
| `limit` | integer | No | 20 | Maximum number of results (1-100) |

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "query": "kubernetes deployment",
      "search_count": 8
    },
    {
      "query": "docker swarm tutorial",
      "search_count": 5
    }
  ]
}
```

**Example:**
```bash
curl "http://localhost/api/analytics/zero-results?days=30&limit=50"
```

---

### Track Click Events

Log user clicks on search results (internal use by frontend).

**Endpoint:**
```http
POST /api/analytics/track-click
```

**Request Body:**
```json
{
  "query": "java programming",
  "position": 1,
  "pdf_path": "/pdfs/java-tutorial.pdf",
  "page": 5
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Click tracked"
}
```

**Example:**
```bash
curl -X POST http://localhost/api/analytics/track-click \
  -H "Content-Type: application/json" \
  -d '{"query":"java","position":1,"pdf_path":"/pdfs/test.pdf","page":1}'
```

---

## Search Endpoints

### Perform Search

Execute a search query across indexed PDFs.

**Endpoint:**
```http
GET /api/search?q={query}&strategy={strategy}&log={log}
```

**Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `q` | string | Yes | - | Search query |
| `strategy` | string | No | auto | Search strategy: `hybrid_ai`, `exact`, `prefix`, or `auto` |
| `log` | integer | No | 1 | Log search analytics: `1` (yes) or `0` (no) |

**Response:**
```json
{
  "status": "success",
  "data": {
    "hits": [
      {
        "_source": {
          "path": "/pdfs/java-tutorial.pdf",
          "page": 12,
          "title": "Java Tutorial",
          "date": "2024-01-15"
        },
        "_score": 8.5,
        "highlight": {
          "text": ["Introduction to <mark>Java</mark> programming"]
        }
      }
    ],
    "total": 45,
    "duration_ms": 28,
    "strategy_used": "hybrid_ai"
  }
}
```

**Search Strategies:**
- `hybrid_ai`: Combines semantic (vector) and keyword search with RRF merging
- `exact`: Exact phrase matching
- `prefix`: Wildcard prefix search (supports `*` and `?`)
- `auto`: Automatically detects best strategy based on query

**Example:**
```bash
# Hybrid AI search (logged)
curl "http://localhost/api/search?q=machine%20learning&strategy=hybrid_ai"

# Quick suggestions (not logged)
curl "http://localhost/api/search?q=python&log=0"

# Auto-detect strategy
curl "http://localhost/api/search?q=java*&strategy=auto"
```

---

## Error Handling

All endpoints follow consistent error response format:

**Error Response:**
```json
{
  "status": "error",
  "message": "Invalid parameter: days must be between 1 and 90",
  "code": "INVALID_PARAMETER"
}
```

**HTTP Status Codes:**
| Code | Meaning |
|------|---------|
| 200 | Success |
| 400 | Bad Request (invalid parameters) |
| 401 | Unauthorized (if authentication enabled) |
| 404 | Not Found |
| 500 | Internal Server Error |

---

## Pagination

Endpoints returning lists support the `limit` parameter:

**Current implementation:**
- `top-queries`: Default 20 results
- `zero-results`: Default 20 results

**Note:** There is no maximum limit validation. Large values may impact performance. Use date filtering for better control:
```bash
# Custom limit
curl "http://localhost/api/analytics/top-queries?limit=50"

# Combine with date range
curl "http://localhost/api/analytics/top-queries?days=90&limit=100"
```

**Not implemented:** Offset-based pagination (e.g., `?offset=20&limit=20` for page 2)

---

## Security & Rate Limiting

**Current status:** ⚠️ NOT IMPLEMENTED

All API endpoints are currently **publicly accessible** with no rate limiting or authentication.

**For production deployment, you MUST implement:**

1. **Rate Limiting** - Prevent abuse
   ```yaml
   # config/packages/rate_limiter.yaml (not configured yet)
   framework:
       rate_limiter:
           analytics_api:
               policy: 'sliding_window'
               limit: 100
               interval: '1 minute'
   ```

2. **API Token Authentication** - Restrict access
   ```php
   // src/Security/ApiTokenAuthenticator.php (does not exist)
   if ($request->headers->get('X-API-Token') !== $_ENV['API_TOKEN']) {
       throw new AuthenticationException('Invalid API token');
   }
   ```

3. **IP Whitelist** - Limit to internal networks
   ```yaml
   # config/packages/security.yaml (not configured)
   access_control:
       - { path: ^/api/analytics, ip: 192.168.1.0/24 }
   ```

4. **HTTPS Only** - Encrypt traffic
   ```yaml
   # config/packages/security.yaml (not configured)
   security:
       access_control:
           - { path: ^/api, requires_channel: https }
   ```

**CORS:** Currently allows all origins (`*`). Restrict in production to specific domains.

---

**Related Documentation:**
- [Analytics Dashboard](analytics.md) - Dashboard usage guide
- [Setup Guide](setup.md) - Setup
