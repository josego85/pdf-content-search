import { mount } from "@vue/test-utils"
import { beforeEach, describe, expect, it, vi } from "vitest"
import Results from "@/components/search/Results.vue"

const makeResult = (id) => ({
	_id: id,
	_score: 1.0,
	_source: {
		title: `Document ${id}`,
		page: 1,
		total_pages: 5,
		path: `/pdfs/doc-${id}.pdf`,
		language: "en",
		date: "2026-01-01T00:00:00+00:00",
	},
	highlight: { text: ["sample text"] },
})

describe("Results", () => {
	beforeEach(() => {
		globalThis.fetch = vi.fn().mockResolvedValue({ ok: true })
	})

	it("renders one ResultCard per result", () => {
		const results = [makeResult("1"), makeResult("2"), makeResult("3")]
		const wrapper = mount(Results, { props: { results, query: "test" } })
		const articles = wrapper.findAll("article")
		expect(articles).toHaveLength(3)
	})

	it("renders zero ResultCards for empty results", () => {
		const wrapper = mount(Results, { props: { results: [], query: "test" } })
		expect(wrapper.findAll("article")).toHaveLength(0)
	})

	it("applies grid layout class when viewMode is grid", () => {
		const results = [makeResult("1")]
		const wrapper = mount(Results, { props: { results, query: "q", viewMode: "grid" } })
		expect(wrapper.find("div").classes().join(" ")).toContain("grid")
	})

	it("applies space-y layout class when viewMode is list", () => {
		const results = [makeResult("1")]
		const wrapper = mount(Results, { props: { results, query: "q", viewMode: "list" } })
		expect(wrapper.find("div").classes().join(" ")).toContain("space-y")
	})

	it("emits open when a ResultCard emits open", async () => {
		const results = [makeResult("1")]
		const wrapper = mount(Results, { props: { results, query: "q" } })
		await wrapper.findComponent({ name: "ResultCard" }).vm.$emit("open", results[0])
		expect(wrapper.emitted("open")).toEqual([[results[0]]])
	})
})
