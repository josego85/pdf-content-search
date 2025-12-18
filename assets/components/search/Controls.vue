<template>
  <div class="max-w-3xl mx-auto mt-3 sm:mt-4 space-y-2">
    <!-- Stats and View Controls -->
    <div class="flex items-center justify-between text-xs sm:text-sm gap-2">
      <div class="flex items-center gap-2 flex-wrap">
        <p class="text-gray-600">
          <span class="hidden sm:inline">Found </span>
          <span class="font-semibold text-gray-900">{{ resultCount }}</span>
          {{ resultCount === 1 ? 'result' : 'results' }}
          <span v-if="duration" class="text-gray-400 hidden sm:inline">in {{ duration }}ms</span>
        </p>
      </div>
      <div class="flex items-center space-x-1 sm:space-x-2">
        <button
          @click="$emit('update:viewMode', 'grid')"
          :class="viewMode === 'grid' ? 'bg-blue-100 text-blue-600' : 'text-gray-400 hover:text-gray-600'"
          class="p-2 sm:p-2.5 rounded-lg transition-colors touch-manipulation"
          title="Grid view"
          aria-label="Grid view"
        >
          <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="currentColor" viewBox="0 0 20 20">
            <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
          </svg>
        </button>
        <button
          @click="$emit('update:viewMode', 'list')"
          :class="viewMode === 'list' ? 'bg-blue-100 text-blue-600' : 'text-gray-400 hover:text-gray-600'"
          class="p-2 sm:p-2.5 rounded-lg transition-colors touch-manipulation"
          title="List view"
          aria-label="List view"
        >
          <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
          </svg>
        </button>
      </div>
    </div>

  </div>
</template>

<script>
export default {
	name: "Controls",
	props: {
		resultCount: {
			type: Number,
			required: true,
		},
		duration: {
			type: Number,
			default: null,
		},
		viewMode: {
			type: String,
			default: "grid",
			validator: (value) => ["grid", "list"].includes(value),
		},
	},
	emits: ["update:viewMode"],
}
</script>
