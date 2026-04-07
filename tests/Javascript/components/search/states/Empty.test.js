import { mount } from "@vue/test-utils"
import { describe, expect, it } from "vitest"
import Empty from "@/components/search/states/Empty.vue"

describe("Empty", () => {
	it("renders the searchQuery prop", () => {
		const wrapper = mount(Empty, { props: { searchQuery: "quantum physics" } })
		expect(wrapper.text()).toContain("quantum physics")
	})

	it("displays no results heading", () => {
		const wrapper = mount(Empty, { props: { searchQuery: "xyz" } })
		expect(wrapper.text()).toContain("No results found")
	})

	it("displays helpful hint text", () => {
		const wrapper = mount(Empty, { props: { searchQuery: "xyz" } })
		expect(wrapper.text()).toContain("Try adjusting your search terms")
	})

	it("emits clear when the clear button is clicked", async () => {
		const wrapper = mount(Empty, { props: { searchQuery: "xyz" } })
		await wrapper.find("button[aria-label='Clear search']").trigger("click")
		expect(wrapper.emitted("clear")).toHaveLength(1)
	})
})
