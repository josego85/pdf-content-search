import { mount } from "@vue/test-utils"
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest"
import Search from "@/components/search/Search.vue"

const makeHit = (id) => ({
	_id: id,
	_score: 1.0,
	_source: {
		title: `Doc ${id}`,
		page: 1,
		total_pages: 5,
		path: `/pdfs/doc-${id}.pdf`,
		language: "en",
		date: "2026-01-01T00:00:00+00:00",
	},
	highlight: { text: ["sample"] },
})

describe("Search", () => {
	beforeEach(() => {
		globalThis.fetch = vi.fn().mockResolvedValue({
			ok: true,
			json: vi.fn().mockResolvedValue({ status: "success", data: { hits: [], duration_ms: 10 } }),
		})
		vi.useFakeTimers()
		vi.spyOn(console, "error").mockImplementation(() => {})
	})

	afterEach(() => {
		vi.useRealTimers()
		vi.restoreAllMocks()
	})

	it("mounts without errors and shows initial state", () => {
		const wrapper = mount(Search)
		expect(wrapper.exists()).toBe(true)
		expect(wrapper.findComponent({ name: "Initial" }).exists()).toBe(true)
	})

	describe("computed: hasResults", () => {
		it("returns false when results is empty", () => {
			const wrapper = mount(Search)
			expect(wrapper.vm.hasResults).toBe(false)
		})

		it("returns true when results has items", async () => {
			const wrapper = mount(Search)
			await wrapper.vm.$nextTick()
			wrapper.vm.results = [makeHit("1")]
			expect(wrapper.vm.hasResults).toBe(true)
		})
	})

	describe("computed: totalPages", () => {
		it("returns 1 for 10 results (PAGE_SIZE=10)", () => {
			const wrapper = mount(Search)
			wrapper.vm.results = Array.from({ length: 10 }, (_, i) => makeHit(String(i)))
			expect(wrapper.vm.totalPages).toBe(1)
		})

		it("returns 2 for 11 results", () => {
			const wrapper = mount(Search)
			wrapper.vm.results = Array.from({ length: 11 }, (_, i) => makeHit(String(i)))
			expect(wrapper.vm.totalPages).toBe(2)
		})
	})

	describe("computed: paginatedResults", () => {
		it("returns first 10 results on page 1", () => {
			const wrapper = mount(Search)
			wrapper.vm.results = Array.from({ length: 15 }, (_, i) => makeHit(String(i)))
			expect(wrapper.vm.paginatedResults).toHaveLength(10)
		})

		it("returns remaining results on last page", () => {
			const wrapper = mount(Search)
			wrapper.vm.results = Array.from({ length: 15 }, (_, i) => makeHit(String(i)))
			wrapper.vm.currentPage = 2
			expect(wrapper.vm.paginatedResults).toHaveLength(5)
		})
	})

	describe("computed: paginationFrom / paginationTo", () => {
		it("returns 1-10 on page 1 with 15 results", () => {
			const wrapper = mount(Search)
			wrapper.vm.results = Array.from({ length: 15 }, (_, i) => makeHit(String(i)))
			expect(wrapper.vm.paginationFrom).toBe(1)
			expect(wrapper.vm.paginationTo).toBe(10)
		})

		it("returns 11-15 on page 2 with 15 results", () => {
			const wrapper = mount(Search)
			wrapper.vm.results = Array.from({ length: 15 }, (_, i) => makeHit(String(i)))
			wrapper.vm.currentPage = 2
			expect(wrapper.vm.paginationFrom).toBe(11)
			expect(wrapper.vm.paginationTo).toBe(15)
		})
	})

	describe("computed: showNoResults", () => {
		it("returns true when query set, no results, no error, no suggestions", () => {
			const wrapper = mount(Search)
			wrapper.vm.searchQuery = "xyz"
			wrapper.vm.results = []
			wrapper.vm.suggestions = []
			wrapper.vm.error = null
			wrapper.vm.isLoadingSuggestions = false
			expect(wrapper.vm.showNoResults).toBe(true)
		})

		it("returns false when results are present", () => {
			const wrapper = mount(Search)
			wrapper.vm.searchQuery = "test"
			wrapper.vm.results = [makeHit("1")]
			expect(wrapper.vm.showNoResults).toBe(false)
		})
	})

	describe("computed: showInitialState", () => {
		it("returns true when no query and no results", () => {
			const wrapper = mount(Search)
			expect(wrapper.vm.showInitialState).toBe(true)
		})

		it("returns false when query is set", () => {
			const wrapper = mount(Search)
			wrapper.vm.searchQuery = "hello"
			expect(wrapper.vm.showInitialState).toBe(false)
		})
	})

	describe("handleCommittedSearch", () => {
		it("performs full search when no suggestion is selected", async () => {
			const wrapper = mount(Search)
			wrapper.vm.searchQuery = "vue testing"
			wrapper.vm.selectedSuggestionIndex = -1
			globalThis.fetch = vi.fn().mockResolvedValue({
				ok: true,
				json: vi.fn().mockResolvedValue({
					status: "success",
					data: { hits: [makeHit("1")], duration_ms: 5 },
				}),
			})
			await wrapper.vm.handleCommittedSearch()
			await wrapper.vm.$nextTick()
			expect(globalThis.fetch).toHaveBeenCalled()
		})

		it("does not fetch when query is shorter than 2 chars", async () => {
			const wrapper = mount(Search)
			wrapper.vm.searchQuery = "x"
			wrapper.vm.selectedSuggestionIndex = -1
			globalThis.fetch = vi.fn()
			await wrapper.vm.handleCommittedSearch()
			expect(globalThis.fetch).not.toHaveBeenCalled()
		})

		it("selects suggestion when selectedSuggestionIndex >= 0", async () => {
			const wrapper = mount(Search)
			const suggestion = makeHit("1")
			wrapper.vm.suggestions = [suggestion]
			wrapper.vm.selectedSuggestionIndex = 0
			wrapper.vm.searchQuery = "test"
			globalThis.fetch = vi.fn().mockResolvedValue({
				ok: true,
				json: vi.fn().mockResolvedValue({
					status: "success",
					data: { hits: [], duration_ms: 5 },
				}),
			})
			await wrapper.vm.handleCommittedSearch()
			expect(wrapper.vm.selectedSuggestionIndex).toBe(-1)
		})
	})

	describe("fetchSuggestions", () => {
		it("populates suggestions on success", async () => {
			const hits = [makeHit("1"), makeHit("2")]
			globalThis.fetch = vi.fn().mockResolvedValue({
				ok: true,
				json: vi.fn().mockResolvedValue({ status: "success", data: { hits } }),
			})
			const wrapper = mount(Search)
			wrapper.vm.searchQuery = "vue"
			await wrapper.vm.fetchSuggestions()
			await wrapper.vm.$nextTick()
			expect(wrapper.vm.suggestions).toHaveLength(2)
		})

		it("clears suggestions on fetch failure", async () => {
			globalThis.fetch = vi.fn().mockRejectedValue(new Error("Network error"))
			const wrapper = mount(Search)
			wrapper.vm.suggestions = [makeHit("1")]
			await wrapper.vm.fetchSuggestions()
			expect(wrapper.vm.suggestions).toHaveLength(0)
		})

		it("clears suggestions when response is not ok", async () => {
			globalThis.fetch = vi.fn().mockResolvedValue({
				ok: false,
				json: vi.fn().mockResolvedValue({ message: "Server error" }),
			})
			const wrapper = mount(Search)
			await wrapper.vm.fetchSuggestions()
			expect(wrapper.vm.suggestions).toHaveLength(0)
		})
	})

	describe("performFullSearch", () => {
		it("sets results on successful search", async () => {
			const hits = [makeHit("1"), makeHit("2")]
			globalThis.fetch = vi.fn().mockResolvedValue({
				ok: true,
				json: vi.fn().mockResolvedValue({
					status: "success",
					data: { hits, duration_ms: 20 },
				}),
			})
			const wrapper = mount(Search)
			wrapper.vm.searchQuery = "test query"
			await wrapper.vm.performFullSearch()
			await wrapper.vm.$nextTick()
			expect(wrapper.vm.results).toHaveLength(2)
			expect(wrapper.vm.currentPage).toBe(1)
		})

		it("sets error when fetch fails", async () => {
			globalThis.fetch = vi.fn().mockRejectedValue(new Error("Network error"))
			const wrapper = mount(Search)
			await wrapper.vm.performFullSearch()
			expect(wrapper.vm.error).toBe("Network error")
			expect(wrapper.vm.results).toHaveLength(0)
		})

		it("sets error state when response is not ok", async () => {
			globalThis.fetch = vi.fn().mockResolvedValue({
				ok: false,
				json: vi.fn().mockResolvedValue({ message: "Server error" }),
			})
			const wrapper = mount(Search)
			await wrapper.vm.performFullSearch()
			await wrapper.vm.$nextTick()
			expect(wrapper.vm.error).toBe("Server error")
			expect(wrapper.vm.results).toHaveLength(0)
		})
	})

	describe("handleBlur", () => {
		it("hides suggestions after delay", () => {
			const wrapper = mount(Search)
			wrapper.vm.isFocused = true
			wrapper.vm.handleBlur()
			expect(wrapper.vm.isFocused).toBe(true) // not yet
			vi.advanceTimersByTime(250)
			expect(wrapper.vm.isFocused).toBe(false)
		})
	})

	describe("selectSuggestion", () => {
		it("clears suggestions and triggers full search", async () => {
			globalThis.fetch = vi.fn().mockResolvedValue({
				ok: true,
				json: vi.fn().mockResolvedValue({ status: "success", data: { hits: [], duration_ms: 5 } }),
			})
			const wrapper = mount(Search)
			wrapper.vm.suggestions = [makeHit("1")]
			wrapper.vm.selectedSuggestionIndex = 0
			wrapper.vm.searchQuery = "test"
			await wrapper.vm.selectSuggestion(makeHit("1"))
			expect(wrapper.vm.suggestions).toHaveLength(0)
			expect(wrapper.vm.selectedSuggestionIndex).toBe(-1)
		})
	})

	describe("openDocument", () => {
		it("calls window.open with viewer URL", () => {
			const openSpy = vi.spyOn(window, "open").mockImplementation(() => {})
			const wrapper = mount(Search)
			wrapper.vm.openDocument(makeHit("1"))
			expect(openSpy).toHaveBeenCalledWith(expect.stringContaining("/viewer"), "_blank")
			openSpy.mockRestore()
		})
	})

	describe("handleSearch (called directly)", () => {
		it("calls fetchSuggestions after debounce when query >= 2 chars", () => {
			const wrapper = mount(Search)
			wrapper.vm.searchQuery = "ab"
			const spy = vi.spyOn(wrapper.vm, "fetchSuggestions").mockResolvedValue(undefined)
			wrapper.vm.handleSearch()
			vi.advanceTimersByTime(350)
			expect(spy).toHaveBeenCalled()
		})

		it("clears results when query is too short", () => {
			const wrapper = mount(Search)
			wrapper.vm.results = [makeHit("1")]
			wrapper.vm.suggestions = [makeHit("2")]
			wrapper.vm.searchQuery = "a"
			wrapper.vm.handleSearch()
			vi.advanceTimersByTime(350)
			expect(wrapper.vm.results).toHaveLength(0)
			expect(wrapper.vm.suggestions).toHaveLength(0)
		})
	})

	describe("methods", () => {
		it("clearSearch resets all state", () => {
			const wrapper = mount(Search)
			wrapper.vm.searchQuery = "test"
			wrapper.vm.results = [makeHit("1")]
			wrapper.vm.suggestions = [makeHit("2")]
			wrapper.vm.error = "some error"
			wrapper.vm.currentPage = 3
			wrapper.vm.clearSearch()
			expect(wrapper.vm.searchQuery).toBe("")
			expect(wrapper.vm.results).toHaveLength(0)
			expect(wrapper.vm.suggestions).toHaveLength(0)
			expect(wrapper.vm.error).toBeNull()
			expect(wrapper.vm.currentPage).toBe(1)
		})

		it("navigateSuggestions down increments selectedSuggestionIndex", () => {
			const wrapper = mount(Search)
			wrapper.vm.suggestions = [makeHit("1"), makeHit("2")]
			wrapper.vm.selectedSuggestionIndex = 0
			wrapper.vm.navigateSuggestions("down")
			expect(wrapper.vm.selectedSuggestionIndex).toBe(1)
		})

		it("navigateSuggestions down does not exceed suggestions length", () => {
			const wrapper = mount(Search)
			wrapper.vm.suggestions = [makeHit("1")]
			wrapper.vm.selectedSuggestionIndex = 0
			wrapper.vm.navigateSuggestions("down")
			expect(wrapper.vm.selectedSuggestionIndex).toBe(0)
		})

		it("navigateSuggestions up decrements selectedSuggestionIndex", () => {
			const wrapper = mount(Search)
			wrapper.vm.selectedSuggestionIndex = 2
			wrapper.vm.navigateSuggestions("up")
			expect(wrapper.vm.selectedSuggestionIndex).toBe(1)
		})

		it("navigateSuggestions up does not go below -1", () => {
			const wrapper = mount(Search)
			wrapper.vm.selectedSuggestionIndex = -1
			wrapper.vm.navigateSuggestions("up")
			expect(wrapper.vm.selectedSuggestionIndex).toBe(-1)
		})

		it("goToPage updates currentPage", () => {
			const wrapper = mount(Search)
			wrapper.vm.goToPage(3)
			expect(wrapper.vm.currentPage).toBe(3)
		})

		it("getViewerLink constructs correct URL", () => {
			const wrapper = mount(Search)
			const result = makeHit("1")
			const link = wrapper.vm.getViewerLink(result)
			expect(link).toContain("path=doc-1.pdf")
			expect(link).toContain("page=1")
		})
	})
})
