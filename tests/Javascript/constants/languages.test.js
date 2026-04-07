import { describe, expect, it } from "vitest"
import {
	getAvailableLanguageCodes,
	getLanguageLabel,
	getLanguageName,
	isValidLanguageCode,
} from "../../../assets/constants/languages.js"

describe("getLanguageName", () => {
	it("returns Spanish name for known code", () => {
		expect(getLanguageName("es")).toBe("Español")
		expect(getLanguageName("en")).toBe("Inglés")
		expect(getLanguageName("de")).toBe("Alemán")
	})

	it("returns uppercase code for unknown language", () => {
		expect(getLanguageName("fr")).toBe("FR")
		expect(getLanguageName("pt")).toBe("PT")
	})
})

describe("getLanguageLabel", () => {
	it("returns uppercase code for known language", () => {
		expect(getLanguageLabel("es")).toBe("ES")
		expect(getLanguageLabel("en")).toBe("EN")
	})

	it("returns uppercase code for unknown language", () => {
		expect(getLanguageLabel("fr")).toBe("FR")
	})

	it("returns empty string for falsy value", () => {
		expect(getLanguageLabel("")).toBe("")
		expect(getLanguageLabel(null)).toBe("")
		expect(getLanguageLabel(undefined)).toBe("")
	})
})

describe("getAvailableLanguageCodes", () => {
	it("returns an array of language code strings", () => {
		const codes = getAvailableLanguageCodes()
		expect(Array.isArray(codes)).toBe(true)
		expect(codes).toContain("es")
		expect(codes).toContain("en")
		expect(codes).toContain("de")
	})

	it("returns only lowercase codes", () => {
		const codes = getAvailableLanguageCodes()
		for (const code of codes) {
			expect(code).toBe(code.toLowerCase())
		}
	})
})

describe("isValidLanguageCode", () => {
	it("returns true for supported language codes", () => {
		expect(isValidLanguageCode("es")).toBe(true)
		expect(isValidLanguageCode("en")).toBe(true)
		expect(isValidLanguageCode("de")).toBe(true)
	})

	it("returns false for unsupported language codes", () => {
		expect(isValidLanguageCode("fr")).toBe(false)
		expect(isValidLanguageCode("pt")).toBe(false)
		expect(isValidLanguageCode("")).toBe(false)
	})
})
