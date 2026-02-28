# TODO

## 🔴 High Priority

### Security & Production Readiness
- [ ] **API Authentication** - Add token-based auth for analytics endpoints
- [ ] **Rate Limiting** - Implement rate limiter for API abuse prevention
- [ ] **HTTPS Enforcement** - Require HTTPS in production for all API endpoints
- [ ] **GDPR Data Retention** - Auto-delete analytics data older than 90 days
  - Command: `app:cleanup-analytics` (cron: daily at 2am)
  - File: `src/Command/CleanupAnalyticsCommand.php`

### Infrastructure
- [ ] **Composer Security Audit** - Add `composer audit` to CI/CD pipeline

## 🟡 Medium Priority

### Analytics Enhancements
- [ ] **Daily Analytics Aggregation** - Pre-aggregate metrics for performance
  - Command: `app:aggregate-analytics` (cron: daily at 1am)
  - Table: `daily_analytics`
  - File: `src/Command/AggregateAnalyticsCommand.php`
- [ ] **Click Position Heatmap** - Add bar chart component (backend ready)
  - File: `assets/components/analytics/ClickPositionHeatmap.vue`
- [ ] **Export Analytics Data** - CSV/JSON export for dashboard data
- [ ] **Filter by Search Strategy** - API endpoint to filter analytics by strategy

### Performance Optimization
- [ ] **Optimize Embedding Generation** - Ollama generates embeddings at ~1s/page on CPU (~18 min for 1k pages); consider GPU acceleration or a faster embedding model

### Testing
- [ ] **JavaScript/Vue Testing** - Add unit tests for frontend components
  - Framework: Vitest or Jest
  - Coverage target: >80%

## 🟢 Low Priority / Future Enhancements

### Analytics Advanced Features
- [ ] Real-time dashboard with WebSocket updates
- [ ] Scheduled email reports
- [ ] Slack integration for alerts
- [ ] ML-powered query suggestions
- [ ] A/B testing framework

### Codebase Quality
- [ ] Refactor entire project for better architecture
- [ ] Add more comprehensive integration tests

---

**Legend:**
- 🔴 High Priority - Security, production readiness, legal compliance
- 🟡 Medium Priority - User-facing features, performance improvements
- 🟢 Low Priority - Nice-to-have features, long-term improvements


**Extras**
- Rector
- Cache
- Index and search markdown, text, image 