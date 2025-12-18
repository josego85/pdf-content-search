<template>
  <div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl sm:rounded-2xl shadow-lg p-6 sm:p-8 md:p-10 lg:p-12 text-center">
      <div class="inline-flex items-center justify-center w-16 h-16 sm:w-20 sm:h-20 bg-gray-100 rounded-full mb-4 sm:mb-6">
        <svg class="w-8 h-8 sm:w-10 sm:h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
      <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-2">No results found</h3>
      <p class="text-sm sm:text-base text-gray-600 mb-1 px-2">
        We couldn't find any documents matching
        <span class="font-semibold text-gray-900 break-words">"{{ searchQuery }}"</span>
      </p>
      <p class="text-xs sm:text-sm text-gray-500 mb-5 sm:mb-6 px-2">Try adjusting your search terms or using different keywords</p>
      <button
        @click="$emit('clear')"
        class="inline-flex items-center px-5 sm:px-6 py-2.5 sm:py-3 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-medium rounded-lg
               transition-all duration-200 shadow-lg shadow-blue-600/20 hover:shadow-xl touch-manipulation text-sm sm:text-base"
        aria-label="Clear search"
      >
        <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        Clear search
      </button>
    </div>
  </div>
</template>

<script>
export default {
	name: "Empty",
	props: {
		searchQuery: {
			type: String,
			required: true,
		},
	},
	emits: ["clear"],
}
</script>
