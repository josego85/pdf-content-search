import { mount } from "@vue/test-utils"
import { describe, expect, it } from "vitest"
import ClickPositionChart from "@/components/analytics/ClickPositionChart.vue"

const sampleData = [
	{ position: 1, ctr: 40, clicks: 80, impressions: 200 },
	{ position: 2, ctr: 25, clicks: 50, impressions: 200 },
	{ position: 3, ctr: 15, clicks: 30, impressions: 200 },
	{ position: 4, ctr: 5, clicks: 10, impressions: 200 },
]

describe("ClickPositionChart", () => {
	it("renders the heading", () => {
		const wrapper = mount(ClickPositionChart)
		expect(wrapper.text()).toContain("Click Position Distribution")
	})

	it("shows no data message when data is empty", () => {
		const wrapper = mount(ClickPositionChart, { props: { data: [] } })
		expect(wrapper.text()).toContain("No click data available")
	})

	it("renders chart when data is provided", () => {
		const wrapper = mount(ClickPositionChart, { props: { data: sampleData } })
		expect(wrapper.find('[data-testid="apexchart"]').exists()).toBe(true)
	})

	it("displays position 1 CTR", () => {
		const wrapper = mount(ClickPositionChart, { props: { data: sampleData } })
		expect(wrapper.text()).toContain("40%")
	})

	it("calculates top 3 capture percentage correctly", () => {
		// total clicks: 170, top3 clicks: 160 → 94%
		const wrapper = mount(ClickPositionChart, { props: { data: sampleData } })
		expect(wrapper.text()).toContain("94%")
	})

	it("shows 0% top3 when no data", () => {
		const wrapper = mount(ClickPositionChart, { props: { data: [] } })
		expect(wrapper.text()).not.toContain("Top 3 capture")
	})
})
