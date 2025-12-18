/**
 * API endpoints and configuration constants.
 * Single source of truth for all API-related configuration.
 */

export const API_ENDPOINTS = {
	TRANSLATE: "/api/translations/translate",
	STATUS: "/api/translations/status",
}

export const POLLING_CONFIG = {
	INTERVAL_MS: 1000, // Poll every 1 second for faster response
	MAX_ATTEMPTS: 300, // Max 300 attempts (300 seconds = 5 minutes total)
	TIMEOUT_MS: 300000, // 300 seconds (5 minutes)
}
