# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Added
- **`PdfPageDocument` DTO**: Typed value object replacing raw arrays for PDF page data, enabling engine-agnostic indexing
- **`SearchResult` DTO**: Typed output from `SearchEngineInterface::search()`, eliminating ES-specific array access (`hits.hits`, `hits.total.value`) from callers

### Changed
- **Elasticsearch indexing**: Replaced per-document HTTP requests with Bulk API (10–50x faster); `refresh_interval` disabled during bulk load and restored with forced refresh after
- **`IndexarPdfsCommand`**: Streaming buffer of 500 pages (`FLUSH_SIZE`) before sending to ES — bounds peak memory regardless of corpus size
- **`SearchQueryBuilder`**: Added `_source` filtering (excludes `text_embedding` vector, ~300 KB saved per search), `timeout: 5s`, and `track_total_hits` bounded to `maxResults`
- **Index mapping**: Added `number_of_replicas: 0` (single-node cluster reaches GREEN health), explicit `language: keyword` mapping, `index_options: offsets` on text fields for faster highlighting, and `int8_hnsw` quantization (4x less RAM for vectors vs float32)
- **Ingest pipeline removed**: Dropped `remove_accents` Painless script — `asciifolding` in the analyzer handles accent normalization for search; highlights now show original accented text from `_source`
- **Architecture**: Removed `IndexManagementInterface` and `PipelineManagementInterface` (ES-specific abstractions with no real swap value); `CreatePdfIndexCommand` now depends on `ElasticsearchService` directly
- **Dead code removal**: Dropped `VectorStoreInterface::searchByVector()`, `indexWithVector()`, `getVectorDimensions()` and their `ElasticsearchVectorStore` implementations — vector indexing goes through `ElasticsearchService` bulk API and kNN queries are built inline; removed `getClient()` factory and its service locator wiring
- **Elasticsearch**: 9.2.4 → 9.3.0 (improved kNN early termination, adaptive HNSW, Zstd compression)
- **Docker Base Images**: Updated to latest stable versions
  - php: 8.4.17-fpm → 8.4.18-fpm
- **CI/CD**: Bumped GitHub Actions dependencies
  - `github/codeql-action`: 4.32.3 → 4.32.4
  - `actions/dependency-review-action`: 4.8.2 → 4.8.3

### Security
- **serialize-javascript**: Patched high severity RCE vulnerability ([GHSA-5c6j-r48x-rmvq](https://github.com/advisories/GHSA-5c6j-r48x-rmvq))
  - Issue: RCE via `RegExp.flags` and `Date.prototype.toISOString()` in versions ≤7.0.2
  - Fixed via `overrides` in `package.json`: 6.0.2 → 7.0.3 (transitivo de `@symfony/webpack-encore`)
- **ajv**: Patched moderate severity ReDoS vulnerability ([GHSA-2g4f-4pwh-qvx6](https://github.com/advisories/GHSA-2g4f-4pwh-qvx6))
  - Issue: ReDoS when using the `$data` option in schema validation
  - ajv: 8.17.1 → 8.18.0
  - file-loader/node_modules/ajv: 6.12.6 → 6.14.0

---
