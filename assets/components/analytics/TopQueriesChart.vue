<template>
  <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Search Queries</h3>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead>
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Query</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Searches</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Avg Results</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Click Rate</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="(item, index) in data" :key="index" class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm text-gray-900">{{ item.query }}</td>
            <td class="px-4 py-3 text-sm text-gray-600 text-right">{{ item.search_count }}</td>
            <td class="px-4 py-3 text-sm text-gray-600 text-right">{{ item.avg_results }}</td>
            <td class="px-4 py-3 text-sm text-right">
              <span class="px-2 py-1 rounded-full text-xs font-medium"
                    :class="getClickRateClass(item.click_rate)">
                {{ item.click_rate }}%
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
defineProps({
	data: { type: Array, default: () => [] },
})

// biome-ignore lint/correctness/noUnusedVariables: Used in Vue template
const getClickRateClass = (rate) => {
	if (rate >= 50) {
		return "bg-green-100 text-green-800"
	}
	if (rate >= 25) {
		return "bg-yellow-100 text-yellow-800"
	}
	return "bg-red-100 text-red-800"
}
</script>
