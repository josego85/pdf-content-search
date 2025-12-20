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
Table showing the most popular search terms with metrics:
- **Query**: Search term
- **Searches**: Number of times searched
- **Avg Results**: Average result count
- **Click Rate**: Percentage of searches where users clicked a result
  - 🟢 Green (>50%): High engagement
  - 🟡 Yellow (25-50%): Medium engagement
  - 🔴 Red (<25%): Low engagement

**Use case**: Optimize content for popular queries and improve low-performing terms.

### 🔍 Time Range Filters

Select the analysis period from the dropdown:
- **Last 7 days** (default)
- **Last 14 days**
- **Last 30 days**
- **Last 90 days**

Charts and metrics update automatically when changed.

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

## Data Privacy

All analytics data is **GDPR-compliant**:
- IP addresses are anonymized (last octet masked)
- No personal data (emails, names) is stored
- Session tracking uses hashed, anonymized IPs

**Data retention**: 90 days (configurable)


## Troubleshooting

For common issues and solutions, see the comprehensive troubleshooting guide:

**📖 [Troubleshooting Guide](troubleshooting.md)** - Analytics, search, Docker, and performance issues

---

**Related Documentation:**
- [REST API Reference](api.md) - Complete API documentation
- [Setup Guide](setup.md) - Installation and configuration
- [Frontend Architecture](frontend.md) - Vue.js components
