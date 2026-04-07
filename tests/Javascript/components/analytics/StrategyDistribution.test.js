import { mount } from "@vue/test-utils"
import { describe, expect, it } from "vitest"
import StrategyDistribution from "@/components/analytics/StrategyDistribution.vue"

describe("StrategyDistribution", () => {
	it("renders the heading", () => {
		const wrapper = mount(StrategyDistribution)
		expect(wrapper.text()).toContain("Search Strategy Usage")
	})

	it("shows no data message when data is empty", () => {
		const wrapper = mount(StrategyDistribution, { props: { data: {} } })
		expect(wrapper.text()).toContain("No data available")
	})

	it("shows no data message when all values are zero", () => {
		const wrapper = mount(StrategyDistribution, {
			props: { data: { hybrid_ai: 0, exact: 0, prefix: 0 } },
		})
		expect(wrapper.text()).toContain("No data available")
	})

	it("renders chart when at least one strategy has data", () => {
		const wrapper = mount(StrategyDistribution, {
			props: { data: { hybrid_ai: 10, exact: 0, prefix: 0 } },
		})
		expect(wrapper.find('[data-testid="apexchart"]').exists()).toBe(true)
	})

	it("maps hybrid_ai, exact and prefix from data prop", () => {
		const wrapper = mount(StrategyDistribution, {
			props: { data: { hybrid_ai: 5, exact: 3, prefix: 2 } },
		})
		expect(wrapper.find('[data-testid="apexchart"]').exists()).toBe(true)
	})
})
