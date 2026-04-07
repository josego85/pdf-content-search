import { mount } from "@vue/test-utils"
import { describe, expect, it } from "vitest"
import Initial from "@/components/search/states/Initial.vue"

describe("Initial", () => {
	it("renders without props", () => {
		const wrapper = mount(Initial)
		expect(wrapper.exists()).toBe(true)
	})

	it("displays AI Hybrid Search feature", () => {
		const wrapper = mount(Initial)
		expect(wrapper.text()).toContain("AI Hybrid Search")
	})

	it("displays Exact Match Mode feature", () => {
		const wrapper = mount(Initial)
		expect(wrapper.text()).toContain("Exact Match Mode")
	})

	it("displays In-PDF Highlighting feature", () => {
		const wrapper = mount(Initial)
		expect(wrapper.text()).toContain("In-PDF Highlighting")
	})
})
