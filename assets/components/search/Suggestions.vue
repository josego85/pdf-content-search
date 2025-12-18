<template>
  <div v-if="suggestions.length > 0" class="absolute top-full left-0 right-0 mt-2 bg-white rounded-xl shadow-2xl border-2 border-gray-200 z-50 max-h-96 overflow-y-auto">
    <div
      v-for="(suggestion, index) in suggestions"
      :key="suggestion._id"
      :class="[
        'px-4 py-3 hover:bg-blue-50 cursor-pointer transition-colors border-b border-gray-100 last:border-b-0',
        { 'bg-blue-50': index === selectedIndex }
      ]"
      @click="$emit('select', suggestion)"
      @mouseenter="$emit('update:selectedIndex', index)"
    >
      <div class="flex items-start gap-3">
        <svg class="w-5 h-5 text-gray-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <div class="flex-1 min-w-0">
          <div class="font-medium text-gray-900 truncate">{{ suggestion._source?.title }}</div>
          <div class="text-sm text-gray-500 mt-1">
            Page {{ suggestion._source?.page }} • {{ getLanguageLabel(suggestion._source?.language) }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { getLanguageLabel } from "../../constants/languages.js"

export default {
	name: "Suggestions",
	props: {
		suggestions: {
			type: Array,
			required: true,
		},
		selectedIndex: {
			type: Number,
			default: -1,
		},
	},
	emits: ["select", "update:selectedIndex"],
	methods: {
		getLanguageLabel(code) {
			return getLanguageLabel(code)
		},
	},
}
</script>
