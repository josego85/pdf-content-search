import { mount } from "@vue/test-utils"
import { describe, expect, it } from "vitest"
import TrendsChart from "@/components/analytics/TrendsChart.vue"

describe("TrendsChart", () => {
	it("renders the heading", () => {
		const wrapper = mount(TrendsChart)
		expect(wrapper.text()).toContain("Search Volume Trends")
	})

	it("shows loading message when data is empty", () => {
		const wrapper = mount(TrendsChart, { props: { data: [] } })
		expect(wrapper.text()).toContain("Loading chart...")
	})

	it("renders chart when data is provided", () => {
		const data = [
			{ date: "2026-04-01", by_strategy: { hybrid_ai: 10, exact: 5, prefix: 2 } },
			{ date: "2026-04-02", by_strategy: { hybrid_ai: 8, exact: 3, prefix: 1 } },
		]
		const wrapper = mount(TrendsChart, { props: { data } })
		expect(wrapper.find('[data-testid="apexchart"]').exists()).toBe(true)
	})

	it("passes days prop to ExportButtons", () => {
		const wrapper = mount(TrendsChart, { props: { data: [], days: 30 } })
		const exportButtons = wrapper.findComponent({ name: "ExportButtons" })
		expect(exportButtons.props("days")).toBe(30)
	})
})
