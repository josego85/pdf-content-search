import { mount } from "@vue/test-utils"
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest"
import ResultCard from "@/components/search/ResultCard.vue"

const defaultSource = {
	title: "Test Document",
	page: 3,
	total_pages: 10,
	path: "/pdfs/test-doc.pdf",
	language: "en",
	date: "2026-01-15T00:00:00+00:00",
}

const makeResult = (sourceOverrides = {}, resultOverrides = {}) => ({
	_id: "1",
	_score: 1.234,
	_source: { ...defaultSource, ...sourceOverrides },
	highlight: { text: ["found <mark>keyword</mark> here"] },
	...resultOverrides,
})

describe("ResultCard", () => {
	beforeEach(() => {
		globalThis.fetch = vi.fn().mockResolvedValue({ ok: true })
		vi.spyOn(window, "open").mockImplementation(() => null)
	})

	afterEach(() => {
		vi.restoreAllMocks()
	})

	describe("viewerLink computed", () => {
		it("constructs URL with encoded path, page, and highlight", () => {
			const wrapper = mount(ResultCard, {
				props: { result: makeResult(), position: 1, query: "keyword" },
			})
			const link = wrapper.find("a")
			expect(link.attributes("href")).toContain("path=test-doc.pdf")
			expect(link.attributes("href")).toContain("page=3")
			expect(link.attributes("href")).toContain("highlight=")
		})
	})

	describe("formatScore", () => {
		it("rounds score to 2 decimal places", () => {
			const wrapper = mount(ResultCard, {
				props: { result: makeResult({}, { _score: 1.5678 }), position: 1, query: "q" },
			})
			expect(wrapper.text()).toContain("1.57")
		})
	})

	describe("formatDate", () => {
		it("formats valid ISO date string", () => {
			const wrapper = mount(ResultCard, {
				props: { result: makeResult(), position: 1, query: "q" },
			})
			expect(wrapper.text()).toMatch(/Jan\s+15,\s+2026/)
		})

		it("returns empty string for missing date without throwing", () => {
			const wrapper = mount(ResultCard, {
				props: { result: makeResult({ date: null }), position: 1, query: "q" },
			})
			expect(wrapper.exists()).toBe(true)
		})
	})

	describe("score badge", () => {
		it("shows RRF score badge when _rrf_score is present", () => {
			const wrapper = mount(ResultCard, {
				props: { result: makeResult({}, { _rrf_score: 0.0164 }), position: 1, query: "q" },
			})
			expect(wrapper.text()).toContain("RRF:")
		})

		it("shows regular score badge when no _rrf_score", () => {
			const wrapper = mount(ResultCard, {
				props: { result: makeResult(), position: 1, query: "q" },
			})
			expect(wrapper.text()).toContain("Score:")
		})
	})

	describe("language badge", () => {
		it("shows language badge when language is set", () => {
			const wrapper = mount(ResultCard, {
				props: { result: makeResult(), position: 1, query: "q" },
			})
			expect(wrapper.text()).toContain("EN")
		})

		it("hides language badge when language is null", () => {
			const wrapper = mount(ResultCard, {
				props: { result: makeResult({ language: null }), position: 1, query: "q" },
			})
			// v-if="result._source?.language" hides the badge
			const badges = wrapper.findAll(".bg-green-50")
			expect(badges).toHaveLength(0)
		})
	})

	describe("highlight text", () => {
		it("renders highlight HTML via v-html", () => {
			const wrapper = mount(ResultCard, {
				props: { result: makeResult(), position: 1, query: "q" },
			})
			expect(wrapper.html()).toContain("<mark>keyword</mark>")
		})
	})

	describe("handleClick", () => {
		it("calls fetch with correct payload when View PDF link is clicked", async () => {
			const result = makeResult()
			const wrapper = mount(ResultCard, { props: { result, position: 2, query: "keyword" } })
			await wrapper.find("a").trigger("click")
			expect(globalThis.fetch).toHaveBeenCalledWith(
				"/api/analytics/track-click",
				expect.objectContaining({
					method: "POST",
					body: expect.stringContaining('"position":2'),
				}),
			)
		})
	})

	describe("open emit", () => {
		it("emits open with result when article is clicked", async () => {
			const result = makeResult()
			const wrapper = mount(ResultCard, { props: { result, position: 1, query: "q" } })
			await wrapper.find("article").trigger("click")
			expect(wrapper.emitted("open")).toEqual([[result]])
		})
	})
})
