import { mount } from "@vue/test-utils"
import { describe, expect, it } from "vitest"
import Bar from "@/components/search/Bar.vue"

const defaultProps = {
	modelValue: "",
	suggestions: [],
	showSuggestions: false,
	selectedIndex: -1,
}

describe("Bar", () => {
	describe("input events", () => {
		it("emits update:modelValue on input", async () => {
			const wrapper = mount(Bar, { props: defaultProps })
			const input = wrapper.find("input")
			await input.setValue("hello")
			expect(wrapper.emitted("update:modelValue")).toBeTruthy()
		})

		it("emits search on Enter key", async () => {
			const wrapper = mount(Bar, { props: { ...defaultProps, modelValue: "test" } })
			await wrapper.find("input").trigger("keydown.enter")
			expect(wrapper.emitted("search")).toHaveLength(1)
		})

		it("emits clear on Escape key", async () => {
			const wrapper = mount(Bar, {
				props: { ...defaultProps, modelValue: "test", showSuggestions: true },
			})
			await wrapper.find("input").trigger("keydown.esc")
			expect(wrapper.emitted("clear")).toHaveLength(1)
		})

		it("emits navigate with 'down' on ArrowDown key", async () => {
			const wrapper = mount(Bar, { props: defaultProps })
			await wrapper.find("input").trigger("keydown.down")
			expect(wrapper.emitted("navigate")).toEqual([["down"]])
		})

		it("emits navigate with 'up' on ArrowUp key", async () => {
			const wrapper = mount(Bar, { props: defaultProps })
			await wrapper.find("input").trigger("keydown.up")
			expect(wrapper.emitted("navigate")).toEqual([["up"]])
		})
	})

	describe("clear button", () => {
		it("is hidden when modelValue is empty", () => {
			const wrapper = mount(Bar, { props: { ...defaultProps, modelValue: "" } })
			expect(wrapper.find("button[aria-label='Clear search']").exists()).toBe(false)
		})

		it("is visible when modelValue is non-empty", () => {
			const wrapper = mount(Bar, { props: { ...defaultProps, modelValue: "hello" } })
			expect(wrapper.find("button[aria-label='Clear search']").exists()).toBe(true)
		})

		it("emits clear when clear button is clicked", async () => {
			const wrapper = mount(Bar, { props: { ...defaultProps, modelValue: "hello" } })
			await wrapper.find("button[aria-label='Clear search']").trigger("click")
			expect(wrapper.emitted("clear")).toHaveLength(1)
		})
	})

	describe("ARIA attributes", () => {
		it("sets aria-expanded to false when showSuggestions is false", () => {
			const wrapper = mount(Bar, { props: { ...defaultProps, showSuggestions: false } })
			expect(wrapper.find("input").attributes("aria-expanded")).toBe("false")
		})

		it("sets aria-expanded to true when showSuggestions is true", () => {
			const wrapper = mount(Bar, { props: { ...defaultProps, showSuggestions: true } })
			expect(wrapper.find("input").attributes("aria-expanded")).toBe("true")
		})

		it("sets aria-activedescendant when selectedIndex >= 0", () => {
			const wrapper = mount(Bar, { props: { ...defaultProps, selectedIndex: 2 } })
			expect(wrapper.find("input").attributes("aria-activedescendant")).toBe("suggestion-2")
		})

		it("does not set aria-activedescendant when selectedIndex is -1", () => {
			const wrapper = mount(Bar, { props: { ...defaultProps, selectedIndex: -1 } })
			expect(wrapper.find("input").attributes("aria-activedescendant")).toBeUndefined()
		})
	})
})
