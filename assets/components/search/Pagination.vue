<template>
  <nav
    v-if="totalPages > 1"
    class="flex items-center justify-center gap-1 sm:gap-2 mt-6 sm:mt-8"
    aria-label="Pagination"
  >
    <!-- Previous -->
    <button
      @click="$emit('update:currentPage', currentPage - 1)"
      :disabled="currentPage === 1"
      class="p-2 sm:p-2.5 rounded-lg transition-colors touch-manipulation"
      :class="currentPage === 1
        ? 'text-gray-300 cursor-not-allowed'
        : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'"
      aria-label="Previous page"
    >
      <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
      </svg>
    </button>

    <!-- Page Numbers -->
    <template v-for="page in visiblePages" :key="page">
      <span
        v-if="page === '...'"
        class="px-2 text-gray-400 text-sm select-none"
      >...</span>
      <button
        v-else
        @click="$emit('update:currentPage', page)"
        class="min-w-[36px] sm:min-w-[40px] h-9 sm:h-10 rounded-lg text-sm font-medium transition-colors touch-manipulation"
        :class="page === currentPage
          ? 'bg-blue-600 text-white shadow-sm'
          : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
        :aria-label="'Page ' + page"
        :aria-current="page === currentPage ? 'page' : undefined"
      >
        {{ page }}
      </button>
    </template>

    <!-- Next -->
    <button
      @click="$emit('update:currentPage', currentPage + 1)"
      :disabled="currentPage === totalPages"
      class="p-2 sm:p-2.5 rounded-lg transition-colors touch-manipulation"
      :class="currentPage === totalPages
        ? 'text-gray-300 cursor-not-allowed'
        : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'"
      aria-label="Next page"
    >
      <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
      </svg>
    </button>
  </nav>
</template>

<script>
export default {
	name: "Pagination",
	props: {
		currentPage: {
			type: Number,
			required: true,
		},
		totalPages: {
			type: Number,
			required: true,
		},
	},
	emits: ["update:currentPage"],
	computed: {
		visiblePages() {
			const total = this.totalPages
			const current = this.currentPage
			const pages = []

			if (total <= 7) {
				for (let i = 1; i <= total; i++) {
					pages.push(i)
				}
				return pages
			}

			// Always show first page
			pages.push(1)

			if (current > 3) {
				pages.push("...")
			}

			// Pages around current
			const start = Math.max(2, current - 1)
			const end = Math.min(total - 1, current + 1)
			for (let i = start; i <= end; i++) {
				pages.push(i)
			}

			if (current < total - 2) {
				pages.push("...")
			}

			// Always show last page
			pages.push(total)

			return pages
		},
	},
}
</script>
