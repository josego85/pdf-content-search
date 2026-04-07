import { mount } from "@vue/test-utils"
import { describe, expect, it } from "vitest"
import Hero from "@/components/search/Hero.vue"

describe("Hero", () => {
	it("renders without errors", () => {
		const wrapper = mount(Hero)
		expect(wrapper.exists()).toBe(true)
	})

	it("displays the application title", () => {
		const wrapper = mount(Hero)
		expect(wrapper.text()).toContain("PDF Content Search")
	})

	it("displays the AI-Powered Search badge", () => {
		const wrapper = mount(Hero)
		expect(wrapper.text()).toContain("AI-Powered Search")
	})

	it("displays the description text", () => {
		const wrapper = mount(Hero)
		expect(wrapper.text()).toContain("Search content within your PDF documents")
	})
})
