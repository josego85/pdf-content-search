import { mount } from "@vue/test-utils"
import { describe, expect, it } from "vitest"
import TopQueriesChart from "@/components/analytics/TopQueriesChart.vue"

const makeItem = (query, search_count, avg_results = 5, click_rate = 30) => ({
	query,
	search_count,
	avg_results,
	click_rate,
})

describe("TopQueriesChart", () => {
	it("renders the heading", () => {
		const wrapper = mount(TopQueriesChart)
		expect(wrapper.text()).toContain("Top Search Queries")
	})

	it("shows empty state when data is empty", () => {
		const wrapper = mount(TopQueriesChart, { props: { data: [] } })
		expect(wrapper.text()).toContain("No data available for this period")
	})

	it("renders rows for provided data", () => {
		const data = [makeItem("vue testing", 10), makeItem("symfony", 8)]
		const wrapper = mount(TopQueriesChart, { props: { data } })
		expect(wrapper.text()).toContain("vue testing")
		expect(wrapper.text()).toContain("symfony")
	})

	it("sorts by search_count descending by default", () => {
		const data = [makeItem("a", 5), makeItem("b", 20), makeItem("c", 10)]
		const wrapper = mount(TopQueriesChart, { props: { data } })
		const rows = wrapper.findAll("tbody tr")
		expect(rows[0].text()).toContain("b")
		expect(rows[1].text()).toContain("c")
		expect(rows[2].text()).toContain("a")
	})

	it("toggles sort direction to ascending on second click of same column", async () => {
		const data = [makeItem("alpha-query", 5), makeItem("beta-query", 20)]
		const wrapper = mount(TopQueriesChart, { props: { data } })
		const searchesHeader = wrapper.findAll("th")[2]
		// default: sortBy=search_count desc → beta-query first
		// one click on same column → flips to asc → alpha-query first
		await searchesHeader.trigger("click")
		const rows = wrapper.findAll("tbody tr")
		expect(rows[0].text()).toContain("alpha-query")
	})

	it("applies amber class to rank 1", () => {
		const data = [makeItem("top", 100)]
		const wrapper = mount(TopQueriesChart, { props: { data } })
		expect(wrapper.find("span.text-amber-500").exists()).toBe(true)
	})

	it("applies green badge for click_rate >= 50", () => {
		const data = [makeItem("high-ctr", 10, 5, 60)]
		const wrapper = mount(TopQueriesChart, { props: { data } })
		expect(wrapper.find("span.bg-green-100").exists()).toBe(true)
	})

	it("applies red badge for click_rate < 25", () => {
		const data = [makeItem("low-ctr", 10, 5, 10)]
		const wrapper = mount(TopQueriesChart, { props: { data } })
		expect(wrapper.find("span.bg-red-100").exists()).toBe(true)
	})

	it("passes days prop to ExportButtons", () => {
		const wrapper = mount(TopQueriesChart, { props: { data: [], days: 14 } })
		const exportButtons = wrapper.findComponent({ name: "ExportButtons" })
		expect(exportButtons.props("days")).toBe(14)
	})
})
