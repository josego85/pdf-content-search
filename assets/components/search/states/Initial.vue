<template>
  <div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-2xl shadow-lg p-12">
      <div class="grid md:grid-cols-3 gap-8">
        <div class="text-center">
          <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-50 rounded-xl mb-4">
            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">Lightning Fast</h3>
          <p class="text-sm text-gray-600">Get instant results powered by Elasticsearch</p>
        </div>
        <div class="text-center">
          <div class="inline-flex items-center justify-center w-16 h-16 bg-green-50 rounded-xl mb-4">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">Smart Highlighting</h3>
          <p class="text-sm text-gray-600">Find matches with intelligent word detection</p>
        </div>
        <div class="text-center">
          <div class="inline-flex items-center justify-center w-16 h-16 bg-purple-50 rounded-xl mb-4">
            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">In-Page Highlighting</h3>
          <p class="text-sm text-gray-600">See highlighted matches directly in PDFs</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Initial'
}
</script>
