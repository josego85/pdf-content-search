# TODO

## 🔴 High Priority

### Security & Production Readiness
- [ ] **API Authentication** - Add token-based auth for analytics endpoints
- [ ] **Rate Limiting** - Implement rate limiter for API abuse prevention
- [ ] **HTTPS Enforcement** - Require HTTPS in production for all API endpoints
- [ ] **GDPR Data Retention** - Auto-delete analytics data older than 90 days
  - Command: `app:cleanup-analytics` (cron: daily at 2am)
  - File: `src/Command/CleanupAnalyticsCommand.php`

## 🟡 Medium Priority

### Analytics Enhancements
- [ ] **Daily Analytics Aggregation** - Pre-aggregate metrics for performance
  - Command: `app:aggregate-analytics` (cron: daily at 1am)
  - Table: `daily_analytics`
  - File: `src/Command/AggregateAnalyticsCommand.php`
- [ ] **Filter by Search Strategy** - API endpoint to filter analytics by strategy

### Performance Optimization
- [x] **Optimize Embedding Generation** - Resolved in PR #131: Ollama migrated to native host + batch embeddings (50 texts/batch) → 6x speedup (~200s for 1k pages, was ~18 min)

### Testing
- [x] **JavaScript/Vue Testing** - Resolved: Vitest + @vue/test-utils, 172 tests, 89% coverage (all thresholds ≥80%), CI job + Husky hook added

## 🟢 Low Priority / Future Enhancements

### Analytics Advanced Features
- [ ] Real-time dashboard with WebSocket updates
- [ ] Scheduled email reports
- [ ] Slack integration for alerts
- [ ] ML-powered query suggestions
- [ ] A/B testing framework

### Codebase Quality
- [ ] Add more comprehensive integration tests

---

**Legend:**
- 🔴 High Priority - Security, production readiness, legal compliance
- 🟡 Medium Priority - User-facing features, performance improvements
- 🟢 Low Priority - Nice-to-have features, long-term improvements


**Extras**
- Cache
- Index and search markdown, text, image 