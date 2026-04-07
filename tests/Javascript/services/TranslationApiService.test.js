import { beforeEach, describe, expect, it, vi } from "vitest"
import { TranslationApiService } from "@/services/TranslationApiService.js"

const mockJson = vi.fn()
const mockResponse = (ok, data) => ({
	ok,
	json: mockJson.mockResolvedValue(data),
})

describe("TranslationApiService", () => {
	let service

	beforeEach(() => {
		service = new TranslationApiService()
		vi.clearAllMocks()
	})

	describe("requestTranslation", () => {
		it("calls fetch with correct endpoint and body", async () => {
			globalThis.fetch = vi.fn().mockResolvedValue(mockResponse(true, { status: "queued" }))

			await service.requestTranslation("doc.pdf", 3, "es")

			expect(globalThis.fetch).toHaveBeenCalledWith(
				"/api/translations/translate",
				expect.objectContaining({
					method: "POST",
					body: JSON.stringify({ filename: "doc.pdf", page: 3, target_language: "es" }),
				}),
			)
		})

		it("returns parsed response on success", async () => {
			const data = { status: "success", translation: "Hola" }
			globalThis.fetch = vi.fn().mockResolvedValue(mockResponse(true, data))

			const result = await service.requestTranslation("doc.pdf", 1, "es")
			expect(result).toEqual(data)
		})

		it("throws error when response is not ok", async () => {
			globalThis.fetch = vi
				.fn()
				.mockResolvedValue(mockResponse(false, { message: "Translation failed" }))

			await expect(service.requestTranslation("doc.pdf", 1, "es")).rejects.toThrow(
				"Translation failed",
			)
		})
	})

	describe("checkTranslationStatus", () => {
		it("calls fetch with STATUS endpoint", async () => {
			globalThis.fetch = vi.fn().mockResolvedValue(mockResponse(true, { status: "pending" }))

			await service.checkTranslationStatus("doc.pdf", 2, "en")

			expect(globalThis.fetch).toHaveBeenCalledWith(
				"/api/translations/status",
				expect.objectContaining({ method: "POST" }),
			)
		})

		it("throws error message from response body when not ok", async () => {
			globalThis.fetch = vi
				.fn()
				.mockResolvedValue(mockResponse(false, { message: "Status check failed" }))

			await expect(service.checkTranslationStatus("doc.pdf", 1, "en")).rejects.toThrow(
				"Status check failed",
			)
		})
	})

	describe("pollForTranslation", () => {
		it("returns result when translation is ready on first poll", async () => {
			const data = { status: "success", ready: true, translation: "Hello" }
			globalThis.fetch = vi.fn().mockResolvedValue(mockResponse(true, data))

			const result = await service.pollForTranslation("doc.pdf", 1, "en")
			expect(result).toEqual(data)
		})

		it("calls onProgress callback with attempt count", async () => {
			const data = { status: "success", ready: true }
			globalThis.fetch = vi.fn().mockResolvedValue(mockResponse(true, data))
			const onProgress = vi.fn()

			await service.pollForTranslation("doc.pdf", 1, "en", onProgress)
			expect(onProgress).toHaveBeenCalledWith(1, expect.any(Number))
		})

		it("throws when status is error", async () => {
			const data = { status: "error", message: "Model failed" }
			globalThis.fetch = vi.fn().mockResolvedValue(mockResponse(true, data))
			// _sleep would make this test wait 300 * 1000ms — mock it to resolve immediately
			vi.spyOn(service, "_sleep").mockResolvedValue(undefined)

			await expect(service.pollForTranslation("doc.pdf", 1, "en")).rejects.toThrow("Model failed")
		})
	})

	describe("_sleep", () => {
		it("resolves after given ms", async () => {
			vi.useFakeTimers()
			const promise = service._sleep(100)
			vi.advanceTimersByTime(100)
			await expect(promise).resolves.toBeUndefined()
			vi.useRealTimers()
		})
	})
})
