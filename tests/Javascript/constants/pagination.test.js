import { describe, expect, it } from "vitest"
import { PAGINATION } from "@/constants/pagination.js"

describe("PAGINATION", () => {
	it("defines PAGE_SIZE as a positive integer", () => {
		expect(typeof PAGINATION.PAGE_SIZE).toBe("number")
		expect(PAGINATION.PAGE_SIZE).toBeGreaterThan(0)
		expect(Number.isInteger(PAGINATION.PAGE_SIZE)).toBe(true)
	})

	it("PAGE_SIZE is 10", () => {
		expect(PAGINATION.PAGE_SIZE).toBe(10)
	})
})
