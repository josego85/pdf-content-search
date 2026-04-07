import { mount } from "@vue/test-utils"
import { describe, expect, it } from "vitest"
import Suggestions from "@/components/search/Suggestions.vue"

const makeSuggestion = (id, title, page = 1, language = "en") => ({
	_id: id,
	_source: { title, page, language },
})

describe("Suggestions", () => {
	it("renders nothing when suggestions array is empty", () => {
		const wrapper = mount(Suggestions, { props: { suggestions: [], selectedIndex: -1 } })
		expect(wrapper.find("div").exists()).toBe(false)
	})

	it("renders a row for each suggestion", () => {
		const suggestions = [makeSuggestion("1", "Doc A"), makeSuggestion("2", "Doc B")]
		const wrapper = mount(Suggestions, { props: { suggestions, selectedIndex: -1 } })
		const rows = wrapper.findAll(".cursor-pointer")
		expect(rows).toHaveLength(2)
	})

	it("renders the title of each suggestion", () => {
		const suggestions = [makeSuggestion("1", "Vue Testing Guide")]
		const wrapper = mount(Suggestions, { props: { suggestions, selectedIndex: -1 } })
		expect(wrapper.text()).toContain("Vue Testing Guide")
	})

	it("displays language code in uppercase", () => {
		const suggestions = [makeSuggestion("1", "Doc", 3, "es")]
		const wrapper = mount(Suggestions, { props: { suggestions, selectedIndex: -1 } })
		expect(wrapper.text()).toContain("ES")
	})

	it("applies highlight class to the selected row", () => {
		const suggestions = [makeSuggestion("1", "A"), makeSuggestion("2", "B")]
		const wrapper = mount(Suggestions, { props: { suggestions, selectedIndex: 1 } })
		const rows = wrapper.findAll(".cursor-pointer")
		expect(rows[1].classes()).toContain("bg-blue-50")
		expect(rows[0].classes()).not.toContain("bg-blue-50")
	})

	it("emits select with the suggestion when a row is clicked", async () => {
		const suggestions = [makeSuggestion("1", "Doc A")]
		const wrapper = mount(Suggestions, { props: { suggestions, selectedIndex: -1 } })
		await wrapper.find(".cursor-pointer").trigger("click")
		expect(wrapper.emitted("select")).toEqual([[suggestions[0]]])
	})

	it("emits update:selectedIndex with the index on mouseenter", async () => {
		const suggestions = [makeSuggestion("1", "A"), makeSuggestion("2", "B")]
		const wrapper = mount(Suggestions, { props: { suggestions, selectedIndex: -1 } })
		const rows = wrapper.findAll(".cursor-pointer")
		await rows[1].trigger("mouseenter")
		expect(wrapper.emitted("update:selectedIndex")).toEqual([[1]])
	})
})
