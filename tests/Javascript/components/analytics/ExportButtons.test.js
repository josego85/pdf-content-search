import { mount } from "@vue/test-utils"
import { beforeEach, describe, expect, it, vi } from "vitest"
import ExportButtons from "@/components/analytics/ExportButtons.vue"

describe("ExportButtons", () => {
	let clickSpy

	beforeEach(() => {
		clickSpy = vi.spyOn(HTMLAnchorElement.prototype, "click").mockImplementation(() => {})
		clickSpy.mockReset()
		clickSpy.mockImplementation(() => {})
	})

	it("renders CSV and JSON buttons", () => {
		const wrapper = mount(ExportButtons, { props: { type: "queries", days: 7 } })
		expect(wrapper.text()).toContain("↓ CSV")
		expect(wrapper.text()).toContain("↓ JSON")
	})

	it("shows loading state during CSV export", async () => {
		const wrapper = mount(ExportButtons, { props: { type: "queries", days: 7 } })
		const buttons = wrapper.findAll("button")
		await buttons[0].trigger("click")
		expect(wrapper.text()).toContain("...")
	})

	it("disables both buttons during export", async () => {
		const wrapper = mount(ExportButtons, { props: { type: "queries", days: 7 } })
		const buttons = wrapper.findAll("button")
		await buttons[0].trigger("click")
		for (const button of wrapper.findAll("button")) {
			expect(button.attributes("disabled")).toBeDefined()
		}
	})

	it("triggers anchor click on CSV export", async () => {
		const wrapper = mount(ExportButtons, { props: { type: "queries", days: 30 } })
		await wrapper.findAll("button")[0].trigger("click")
		expect(clickSpy).toHaveBeenCalledTimes(1)
	})

	it("triggers anchor click on JSON export", async () => {
		const wrapper = mount(ExportButtons, { props: { type: "clicks", days: 14 } })
		await wrapper.findAll("button")[1].trigger("click")
		expect(clickSpy).toHaveBeenCalledTimes(1)
	})
})
