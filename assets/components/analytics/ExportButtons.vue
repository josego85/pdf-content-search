<template>
  <div class="flex gap-1">
    <button
      @click="doExport('csv')"
      :disabled="exportingFormat !== null"
      class="text-xs px-2 py-1 border rounded transition-colors disabled:cursor-not-allowed"
      :class="exportingFormat === 'csv' ? 'text-blue-600 border-blue-300 bg-blue-50' : 'text-gray-500 hover:text-gray-700 border-gray-200 hover:border-gray-300'"
    >{{ exportingFormat === 'csv' ? '...' : '↓ CSV' }}</button>
    <button
      @click="doExport('json')"
      :disabled="exportingFormat !== null"
      class="text-xs px-2 py-1 border rounded transition-colors disabled:cursor-not-allowed"
      :class="exportingFormat === 'json' ? 'text-blue-600 border-blue-300 bg-blue-50' : 'text-gray-500 hover:text-gray-700 border-gray-200 hover:border-gray-300'"
    >{{ exportingFormat === 'json' ? '...' : '↓ JSON' }}</button>
  </div>
</template>

<script setup>
import { ref } from "vue"

const props = defineProps({
	type: { type: String, required: true },
	days: { type: [String, Number], required: true },
})

const exportingFormat = ref(null)

// biome-ignore lint/correctness/noUnusedVariables: used in template via @click
const doExport = (format) => {
	exportingFormat.value = format
	const a = document.createElement("a")
	a.href = `/api/analytics/export?type=${props.type}&format=${format}&days=${props.days}`
	a.click()
	setTimeout(() => {
		exportingFormat.value = null
	}, 1000)
}
</script>
