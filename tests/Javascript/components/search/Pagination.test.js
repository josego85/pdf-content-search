import { mount } from "@vue/test-utils"
import { describe, expect, it } from "vitest"
import Pagination from "@/components/search/Pagination.vue"

describe("Pagination", () => {
	describe("visiblePages computed", () => {
		it("returns all pages when totalPages <= 7", () => {
			const wrapper = mount(Pagination, { props: { currentPage: 1, totalPages: 5 } })
			const buttons = wrapper.findAll("button[aria-label^='Page']")
			expect(buttons).toHaveLength(5)
		})

		it("returns all 7 pages when totalPages === 7", () => {
			const wrapper = mount(Pagination, { props: { currentPage: 1, totalPages: 7 } })
			const buttons = wrapper.findAll("button[aria-label^='Page']")
			expect(buttons).toHaveLength(7)
		})

		it("inserts ellipsis at end when cursor is at start", () => {
			const wrapper = mount(Pagination, { props: { currentPage: 1, totalPages: 10 } })
			expect(wrapper.text()).toContain("...")
			expect(wrapper.text()).toContain("10")
		})

		it("inserts ellipsis at both sides when cursor is in middle", () => {
			const wrapper = mount(Pagination, { props: { currentPage: 5, totalPages: 10 } })
			const ellipses = wrapper.findAll("span")
			expect(ellipses.length).toBeGreaterThanOrEqual(2)
		})

		it("inserts ellipsis at start when cursor is at end", () => {
			const wrapper = mount(Pagination, { props: { currentPage: 10, totalPages: 10 } })
			expect(wrapper.text()).toContain("...")
			expect(wrapper.text()).toContain("1")
		})
	})

	describe("navigation buttons", () => {
		it("disables previous button on page 1", () => {
			const wrapper = mount(Pagination, { props: { currentPage: 1, totalPages: 5 } })
			const prevBtn = wrapper.find("button[aria-label='Previous page']")
			expect(prevBtn.attributes("disabled")).toBeDefined()
		})

		it("disables next button on last page", () => {
			const wrapper = mount(Pagination, { props: { currentPage: 5, totalPages: 5 } })
			const nextBtn = wrapper.find("button[aria-label='Next page']")
			expect(nextBtn.attributes("disabled")).toBeDefined()
		})

		it("enables previous button when not on first page", () => {
			const wrapper = mount(Pagination, { props: { currentPage: 3, totalPages: 5 } })
			const prevBtn = wrapper.find("button[aria-label='Previous page']")
			expect(prevBtn.attributes("disabled")).toBeUndefined()
		})

		it("emits update:currentPage with previous page on prev click", async () => {
			const wrapper = mount(Pagination, { props: { currentPage: 3, totalPages: 5 } })
			await wrapper.find("button[aria-label='Previous page']").trigger("click")
			expect(wrapper.emitted("update:currentPage")).toEqual([[2]])
		})

		it("emits update:currentPage with next page on next click", async () => {
			const wrapper = mount(Pagination, { props: { currentPage: 3, totalPages: 5 } })
			await wrapper.find("button[aria-label='Next page']").trigger("click")
			expect(wrapper.emitted("update:currentPage")).toEqual([[4]])
		})

		it("emits update:currentPage with correct page on page button click", async () => {
			const wrapper = mount(Pagination, { props: { currentPage: 1, totalPages: 5 } })
			await wrapper.find("button[aria-label='Page 3']").trigger("click")
			expect(wrapper.emitted("update:currentPage")).toEqual([[3]])
		})
	})

	describe("active page indicator", () => {
		it("marks current page button with aria-current=page", () => {
			const wrapper = mount(Pagination, { props: { currentPage: 2, totalPages: 5 } })
			const activeBtn = wrapper.find("button[aria-current='page']")
			expect(activeBtn.text()).toBe("2")
		})
	})

	it("does not render when totalPages <= 1", () => {
		const wrapper = mount(Pagination, { props: { currentPage: 1, totalPages: 1 } })
		expect(wrapper.find("nav").exists()).toBe(false)
	})
})
