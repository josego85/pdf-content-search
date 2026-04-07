import { describe, expect, it } from "vitest"
import { API_ENDPOINTS, POLLING_CONFIG } from "@/constants/api.js"

describe("API_ENDPOINTS", () => {
	it("defines TRANSLATE endpoint", () => {
		expect(API_ENDPOINTS.TRANSLATE).toBe("/api/translations/translate")
	})

	it("defines STATUS endpoint", () => {
		expect(API_ENDPOINTS.STATUS).toBe("/api/translations/status")
	})
})

describe("POLLING_CONFIG", () => {
	it("defines INTERVAL_MS", () => {
		expect(typeof POLLING_CONFIG.INTERVAL_MS).toBe("number")
		expect(POLLING_CONFIG.INTERVAL_MS).toBeGreaterThan(0)
	})

	it("defines MAX_ATTEMPTS", () => {
		expect(typeof POLLING_CONFIG.MAX_ATTEMPTS).toBe("number")
		expect(POLLING_CONFIG.MAX_ATTEMPTS).toBeGreaterThan(0)
	})

	it("defines TIMEOUT_MS", () => {
		expect(typeof POLLING_CONFIG.TIMEOUT_MS).toBe("number")
	})
})
