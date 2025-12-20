<template>
  <div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl sm:rounded-2xl shadow-lg p-6 sm:p-8 md:p-10 lg:p-12">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 sm:gap-8">
        <div class="text-center">
          <div class="inline-flex items-center justify-center w-14 h-14 sm:w-16 sm:h-16 bg-purple-50 rounded-xl mb-3 sm:mb-4">
            <svg class="w-7 h-7 sm:w-8 sm:h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
            </svg>
          </div>
          <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-1.5 sm:mb-2">AI Hybrid Search</h3>
          <p class="text-xs sm:text-sm text-gray-600">Combines keyword matching with semantic understanding</p>
        </div>
        <div class="text-center">
          <div class="inline-flex items-center justify-center w-14 h-14 sm:w-16 sm:h-16 bg-blue-50 rounded-xl mb-3 sm:mb-4">
            <svg class="w-7 h-7 sm:w-8 sm:h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
          </div>
          <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-1.5 sm:mb-2">Exact Match Mode</h3>
          <p class="text-xs sm:text-sm text-gray-600">Use quotes for precise phrase matching</p>
        </div>
        <div class="text-center">
          <div class="inline-flex items-center justify-center w-14 h-14 sm:w-16 sm:h-16 bg-green-50 rounded-xl mb-3 sm:mb-4">
            <svg class="w-7 h-7 sm:w-8 sm:h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
            </svg>
          </div>
          <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-1.5 sm:mb-2">In-PDF Highlighting</h3>
          <p class="text-xs sm:text-sm text-gray-600">View results with highlights directly in documents</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
	name: "Initial",
}
</script>
