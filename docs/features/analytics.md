# Analytics Dashboard

Real-time insights into search behavior, performance, and user engagement.

## Access Dashboard

Open in your browser:

```
http://localhost/analytics
```

## Features

### 📊 Key Performance Indicators (KPIs)

Four real-time metrics displayed as cards:

- **Total Searches**: Total search volume across the selected period
- **Avg Response Time**: Average search performance in milliseconds
- **Success Rate**: Percentage of searches that returned results (non-zero results)
- **Unique Sessions**: Number of distinct user sessions based on anonymized IPs

### 📈 Interactive Charts

#### 1. Search Volume Trends
Line chart showing daily search patterns broken down by strategy:
- **Hybrid AI** (purple): Semantic + keyword search with RRF merging
- **Exact** (green): Exact phrase matching
- **Prefix** (yellow): Wildcard prefix searches

**Use case**: Identify peak usage times and strategy preferences over time.

#### 2. Strategy Distribution
Donut chart visualizing the percentage breakdown of search strategies used.

**Use case**: Understand which search modes users prefer (AI vs traditional).

#### 3. Top Queries
Sortable, paginated table showing the most popular search terms with metrics:
- **Query**: Search term
- **Searches**: Number of times searched
- **Avg Results**: Average result count
- **Click Rate**: Percentage of searches where users clicked a result
  - 🟢 Green (>50%): High engagement
  - 🟡 Yellow (25-50%): Medium engagement
  - 🔴 Red (<25%): Low engagement

Supports client-side pagination and column sorting. Default: 10 rows per page.

**Use case**: Optimize content for popular queries and improve low-performing terms.

#### 4. Click Position Distribution
Bar chart showing how often users click each result position (1–10):
- **Position**: Result rank shown to the user
- **Clicks**: Number of times that position was clicked
- **Impressions**: How many times that position was shown (`displayed_results_count`)
- **CTR**: Click-Through Rate per position (`clicks / impressions × 100`)

**Use case**: Validate result ranking quality. Position 1 should have the highest CTR. A flat distribution indicates poor ranking.

### 🔍 Time Range Filters

Select the analysis period from the dropdown:
- **Last 7 days** (default)
- **Last 14 days**
- **Last 30 days**
- **Last 90 days**

Charts and metrics update automatically when changed.

### 📥 Data Export

Each dashboard panel has contextual export buttons:

- **↓ CSV**: Download panel data as a spreadsheet-compatible CSV file
- **↓ JSON**: Download panel data as structured JSON

**Available export types:** `overview`, `trends`, `top-queries`

```bash
# Export via API directly
curl "http://localhost/api/analytics/export?type=overview&format=csv&days=30"
curl "http://localhost/api/analytics/export?type=top-queries&format=json&days=7"
```

Exports are non-blocking: the browser downloads the file directly without reloading the page.

## API Access

For programmatic access to analytics data, use the REST API endpoints.

**📖 Complete API reference:** [api.md](api.md)

**Quick examples:**
```bash
# Get overview metrics
curl http://localhost/api/analytics/overview?days=7

# Top queries
curl http://localhost/api/analytics/top-queries?days=30&limit=10

# Daily trends
curl http://localhost/api/analytics/trends?days=14
```

## Use Cases

### 1. Content Strategy
**Goal**: Identify what users are looking for and content gaps.

**Actions:**
- Review **Top Queries** to understand popular topics
- Check **Zero-Result Queries** to find missing content
- Add PDFs covering high-demand topics with zero results

**Example**: If "kubernetes deployment" has 8 zero-result searches, add relevant PDFs.

### 2. Performance Monitoring
**Goal**: Ensure fast, reliable search experience.

**Actions:**
- Monitor **Avg Response Time** KPI (target: <100ms)
- Check **Success Rate** (target: >95%)
- Investigate slow queries via API and optimize Elasticsearch indices

**Example**: If response time spikes, check Elasticsearch cluster health.

### 3. UX Optimization
**Goal**: Improve search result relevance.

**Actions:**
- Analyze **Click Rates** in Top Queries table
- Low click rates (<25%) indicate poor result relevance
- Review queries with high searches but low clicks
- Adjust search algorithms or boost specific document fields

**Example**: Query "python testing" has 50 searches but 10% click rate → results not relevant.

### 4. AI vs Traditional Search
**Goal**: Measure effectiveness of Hybrid AI search.

**Actions:**
- Compare **Strategy Distribution** percentages
- Check **Click Rates** by strategy using API
- If Hybrid AI has higher click rates, promote it as default

**Example**: Hybrid AI: 80% click rate vs Exact: 45% → AI is more effective.

## Data Accuracy

### Impressions vs Total Results

The system distinguishes two counts per search event:

| Field | Value | Used For |
|-------|-------|----------|
| `results_count` | Elasticsearch total matches (up to `ELASTICSEARCH_MAX_RESULTS`) | Raw relevance signal |
| `displayed_results_count` | `min(results_count, SEARCH_PAGE_SIZE)` | Impressions for CTR calculation |

Using `displayed_results_count` ensures CTR is calculated against what the user actually saw — not the full Elasticsearch result set. This is captured at search time, so changing `SEARCH_PAGE_SIZE` later does not corrupt historical data.

## Data Privacy

All analytics data is **GDPR-compliant**:
- IP addresses are anonymized (last octet zeroed: `192.168.1.100` → `192.168.1.0`)
- IPv6 anonymized to first 4 groups (`2001:db8:85a3:0000::`)
- No personal data (emails, names) is stored
- Session tracking uses Symfony session IDs (no cookies beyond the session)

**Data retention**: 90 days (configurable)


## Troubleshooting

For common issues and solutions, see the comprehensive troubleshooting guide:

**📖 [Troubleshooting Guide](troubleshooting.md)** - Analytics, search, Docker, and performance issues

---

**Related Documentation:**
- [REST API Reference](api.md) - Complete API documentation
- [Setup Guide](setup.md) - Installation and configuration
- [Frontend Architecture](frontend.md) - Vue.js components
