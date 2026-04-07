import { mount } from "@vue/test-utils"
import { describe, expect, it } from "vitest"
import Loading from "@/components/search/states/Loading.vue"

describe("Loading", () => {
	it("renders without props", () => {
		const wrapper = mount(Loading)
		expect(wrapper.exists()).toBe(true)
	})

	it("displays searching message", () => {
		const wrapper = mount(Loading)
		expect(wrapper.text()).toContain("Searching documents...")
	})

	it("displays wait message", () => {
		const wrapper = mount(Loading)
		expect(wrapper.text()).toContain("This should only take a moment")
	})
})
