import { mount } from "@vue/test-utils"
import { describe, expect, it } from "vitest"
import Controls from "@/components/search/Controls.vue"

const defaultProps = { resultCount: 10, from: 1, to: 10, viewMode: "grid" }

describe("Controls", () => {
	describe("result count display", () => {
		it("shows singular 'result' for 1 result", () => {
			const wrapper = mount(Controls, { props: { ...defaultProps, resultCount: 1, to: 1 } })
			expect(wrapper.text()).toContain("result")
			expect(wrapper.text()).not.toContain("results")
		})

		it("shows plural 'results' for multiple results", () => {
			const wrapper = mount(Controls, { props: defaultProps })
			expect(wrapper.text()).toContain("results")
		})

		it("displays from-to-of correctly", () => {
			const wrapper = mount(Controls, {
				props: { ...defaultProps, resultCount: 25, from: 11, to: 20 },
			})
			expect(wrapper.text()).toContain("11")
			expect(wrapper.text()).toContain("20")
			expect(wrapper.text()).toContain("25")
		})
	})

	describe("view mode buttons", () => {
		it("emits update:viewMode with 'grid' on grid button click", async () => {
			const wrapper = mount(Controls, { props: { ...defaultProps, viewMode: "list" } })
			await wrapper.find("button[aria-label='Grid view']").trigger("click")
			expect(wrapper.emitted("update:viewMode")).toEqual([["grid"]])
		})

		it("emits update:viewMode with 'list' on list button click", async () => {
			const wrapper = mount(Controls, { props: defaultProps })
			await wrapper.find("button[aria-label='List view']").trigger("click")
			expect(wrapper.emitted("update:viewMode")).toEqual([["list"]])
		})

		it("applies blue class to active grid button", () => {
			const wrapper = mount(Controls, { props: { ...defaultProps, viewMode: "grid" } })
			const gridBtn = wrapper.find("button[aria-label='Grid view']")
			expect(gridBtn.classes()).toContain("bg-blue-100")
		})

		it("applies blue class to active list button", () => {
			const wrapper = mount(Controls, { props: { ...defaultProps, viewMode: "list" } })
			const listBtn = wrapper.find("button[aria-label='List view']")
			expect(listBtn.classes()).toContain("bg-blue-100")
		})
	})
})
