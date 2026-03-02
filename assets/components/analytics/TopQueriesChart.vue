<template>
  <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Search Queries</h3>

    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead>
          <tr>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-10">#</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Query</th>

            <!-- Sortable: Searches -->
            <th
              class="px-4 py-2 text-right text-xs font-medium uppercase cursor-pointer select-none group"
              :class="sortBy === 'search_count' ? 'text-blue-600' : 'text-gray-500 hover:text-gray-700'"
              @click="toggleSort('search_count')"
            >
              <span class="inline-flex items-center justify-end gap-1">
                Searches
                <svg class="w-3 h-3 flex-shrink-0" viewBox="0 0 12 12" fill="currentColor">
                  <path :opacity="sortBy === 'search_count' && sortDir === 'asc' ? 1 : 0.25" d="M6 1 L10 5 L2 5 Z" />
                  <path :opacity="sortBy === 'search_count' && sortDir === 'desc' ? 1 : 0.25" d="M6 11 L2 7 L10 7 Z" />
                </svg>
              </span>
            </th>

            <!-- Sortable: Avg Results -->
            <th
              class="px-4 py-2 text-right text-xs font-medium uppercase cursor-pointer select-none"
              :class="sortBy === 'avg_results' ? 'text-blue-600' : 'text-gray-500 hover:text-gray-700'"
              @click="toggleSort('avg_results')"
            >
              <span class="inline-flex items-center justify-end gap-1">
                Avg Results
                <svg class="w-3 h-3 flex-shrink-0" viewBox="0 0 12 12" fill="currentColor">
                  <path :opacity="sortBy === 'avg_results' && sortDir === 'asc' ? 1 : 0.25" d="M6 1 L10 5 L2 5 Z" />
                  <path :opacity="sortBy === 'avg_results' && sortDir === 'desc' ? 1 : 0.25" d="M6 11 L2 7 L10 7 Z" />
                </svg>
              </span>
            </th>

            <!-- Sortable: Click Rate -->
            <th
              class="px-4 py-2 text-right text-xs font-medium uppercase cursor-pointer select-none"
              :class="sortBy === 'click_rate' ? 'text-blue-600' : 'text-gray-500 hover:text-gray-700'"
              @click="toggleSort('click_rate')"
            >
              <span class="inline-flex items-center justify-end gap-1">
                Click Rate
                <svg class="w-3 h-3 flex-shrink-0" viewBox="0 0 12 12" fill="currentColor">
                  <path :opacity="sortBy === 'click_rate' && sortDir === 'asc' ? 1 : 0.25" d="M6 1 L10 5 L2 5 Z" />
                  <path :opacity="sortBy === 'click_rate' && sortDir === 'desc' ? 1 : 0.25" d="M6 11 L2 7 L10 7 Z" />
                </svg>
              </span>
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-if="data.length === 0">
            <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400">
              No data available for this period
            </td>
          </tr>
          <tr
            v-for="(item, index) in paginatedData"
            :key="item.query"
            class="hover:bg-gray-50 transition-colors"
          >
            <!-- Ranking # with medal for top 3 -->
            <td class="px-4 py-2 text-sm font-medium w-10">
              <span :class="getRankClass(rangeStart + index)">
                {{ rangeStart + index }}
              </span>
            </td>

            <!-- Query -->
            <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ item.query }}</td>

            <!-- Searches with mini bar -->
            <td class="px-4 py-2 text-sm text-right">
              <div class="flex items-center justify-end gap-2">
                <div class="w-16 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                  <div
                    class="h-full bg-blue-400 rounded-full"
                    :style="{ width: getBarWidth(item.search_count) }"
                  />
                </div>
                <span class="text-gray-700 tabular-nums w-6 text-right">{{ item.search_count }}</span>
              </div>
            </td>

            <!-- Avg Results -->
            <td class="px-4 py-2 text-sm text-gray-500 text-right tabular-nums">{{ item.avg_results }}</td>

            <!-- Click Rate -->
            <td class="px-4 py-2 text-sm text-right">
              <span
                class="px-2 py-0.5 rounded-full text-xs font-medium"
                :class="getClickRateClass(item.click_rate)"
              >
                {{ item.click_rate }}%
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination centered + showing text below -->
    <div v-if="totalPages > 1" class="mt-4 pt-4 border-t border-gray-100">
      <Pagination
        :currentPage="currentPage"
        :totalPages="totalPages"
        @update:currentPage="currentPage = $event"
      />
      <p class="text-center text-xs text-gray-400 mt-2">
        Showing {{ rangeStart }}–{{ rangeEnd }} of {{ data.length }} queries
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from "vue"
// biome-ignore lint/correctness/noUnusedImports: Component used in template
import Pagination from "../search/Pagination.vue"

const props = defineProps({
	data: { type: Array, default: () => [] },
})

const PAGE_SIZE = 10
const currentPage = ref(1)
const sortBy = ref("search_count")
const sortDir = ref("desc")

// Reset page and sort when data changes (period selector)
watch(
	() => props.data,
	() => {
		currentPage.value = 1
		sortBy.value = "search_count"
		sortDir.value = "desc"
	},
)

// biome-ignore lint/correctness/noUnusedVariables: Used in Vue template
const toggleSort = (field) => {
	if (sortBy.value === field) {
		sortDir.value = sortDir.value === "desc" ? "asc" : "desc"
	} else {
		sortBy.value = field
		sortDir.value = "desc"
	}
	currentPage.value = 1
}

const sortedData = computed(() => {
	return [...props.data].sort((a, b) => {
		const aVal = a[sortBy.value]
		const bVal = b[sortBy.value]
		return sortDir.value === "desc" ? bVal - aVal : aVal - bVal
	})
})

// biome-ignore lint/correctness/noUnusedVariables: Used in Vue template
const totalPages = computed(() => Math.ceil(sortedData.value.length / PAGE_SIZE))
const rangeStart = computed(() => (currentPage.value - 1) * PAGE_SIZE + 1)
const rangeEnd = computed(() => Math.min(currentPage.value * PAGE_SIZE, sortedData.value.length))

// biome-ignore lint/correctness/noUnusedVariables: Used in Vue template
const paginatedData = computed(() => sortedData.value.slice(rangeStart.value - 1, rangeEnd.value))

const maxSearchCount = computed(() => Math.max(...props.data.map((i) => i.search_count), 1))

// biome-ignore lint/correctness/noUnusedVariables: Used in Vue template
const getBarWidth = (count) => `${Math.round((count / maxSearchCount.value) * 100)}%`

// biome-ignore lint/correctness/noUnusedVariables: Used in Vue template
const getRankClass = (rank) => {
	if (rank === 1) {
		return "text-amber-500 font-bold"
	}
	if (rank === 2) {
		return "text-slate-400 font-semibold"
	}
	if (rank === 3) {
		return "text-orange-400 font-semibold"
	}
	return "text-gray-400"
}

// biome-ignore lint/correctness/noUnusedVariables: Used in Vue template
const getClickRateClass = (rate) => {
	if (rate === 0) {
		return "bg-gray-100 text-gray-500"
	}
	if (rate >= 50) {
		return "bg-green-100 text-green-800"
	}
	if (rate >= 25) {
		return "bg-yellow-100 text-yellow-800"
	}
	return "bg-red-100 text-red-700"
}
</script>
