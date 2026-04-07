import { mount } from "@vue/test-utils"
import { describe, expect, it } from "vitest"
import KPICard from "@/components/analytics/KPICard.vue"

describe("KPICard", () => {
	describe("formattedValue", () => {
		it("formats a number with toLocaleString", () => {
			const wrapper = mount(KPICard, { props: { title: "Total", value: 1234 } })
			expect(wrapper.text()).toContain("1,234")
		})

		it("appends suffix to numeric value", () => {
			const wrapper = mount(KPICard, { props: { title: "Rate", value: 42, suffix: "%" } })
			expect(wrapper.text()).toContain("42%")
		})

		it("passes string value through unchanged", () => {
			const wrapper = mount(KPICard, { props: { title: "Status", value: "N/A" } })
			expect(wrapper.text()).toContain("N/A")
		})
	})

	describe("trendClass", () => {
		it("applies green class for positive trend", () => {
			const wrapper = mount(KPICard, { props: { title: "T", value: 1, trend: 5 } })
			expect(wrapper.find("p.text-green-600").exists()).toBe(true)
		})

		it("applies red class for negative trend", () => {
			const wrapper = mount(KPICard, { props: { title: "T", value: 1, trend: -3 } })
			expect(wrapper.find("p.text-red-600").exists()).toBe(true)
		})

		it("does not render trend line when trend is null", () => {
			const wrapper = mount(KPICard, { props: { title: "T", value: 1, trend: null } })
			expect(wrapper.find("p.text-green-600").exists()).toBe(false)
			expect(wrapper.find("p.text-red-600").exists()).toBe(false)
		})
	})

	describe("rendering", () => {
		it("renders the title", () => {
			const wrapper = mount(KPICard, { props: { title: "Searches Today", value: 0 } })
			expect(wrapper.text()).toContain("Searches Today")
		})

		it("renders the icon when provided", () => {
			const wrapper = mount(KPICard, { props: { title: "T", value: 0, icon: "🔍" } })
			expect(wrapper.text()).toContain("🔍")
		})

		it("does not render icon element when icon is empty", () => {
			const wrapper = mount(KPICard, { props: { title: "T", value: 0, icon: "" } })
			expect(wrapper.find(".text-4xl").exists()).toBe(false)
		})

		it("shows trend percentage with direction arrow", () => {
			const wrapper = mount(KPICard, { props: { title: "T", value: 1, trend: 10 } })
			expect(wrapper.text()).toContain("10%")
			expect(wrapper.text()).toContain("↑")
		})

		it("shows down arrow for negative trend", () => {
			const wrapper = mount(KPICard, { props: { title: "T", value: 1, trend: -7 } })
			expect(wrapper.text()).toContain("7%")
			expect(wrapper.text()).toContain("↓")
		})
	})
})
