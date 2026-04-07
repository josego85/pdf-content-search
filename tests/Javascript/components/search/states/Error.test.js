import { mount } from "@vue/test-utils"
import { describe, expect, it } from "vitest"
import Error from "@/components/search/states/Error.vue"

describe("Error", () => {
	it("renders the message prop", () => {
		const wrapper = mount(Error, { props: { message: "Something went wrong" } })
		expect(wrapper.text()).toContain("Something went wrong")
	})

	it("renders the error occurred heading", () => {
		const wrapper = mount(Error, { props: { message: "Network failure" } })
		expect(wrapper.text()).toContain("Error occurred")
	})

	it("emits close when the close button is clicked", async () => {
		const wrapper = mount(Error, { props: { message: "Oops" } })
		await wrapper.find("button[aria-label='Close error']").trigger("click")
		expect(wrapper.emitted("close")).toHaveLength(1)
	})
})
