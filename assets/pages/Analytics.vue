<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Analytics Dashboard</h1>
        <p class="text-gray-600 mt-2">Search insights and performance metrics</p>
      </div>

      <!-- Date Range Selector -->
      <div class="mb-6 flex justify-end">
        <select
          v-model="selectedPeriod"
          @change="loadData"
          class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="7">Last 7 days</option>
          <option value="14">Last 14 days</option>
          <option value="30">Last 30 days</option>
          <option value="90">Last 90 days</option>
        </select>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="flex items-center justify-center py-12">
        <div class="text-gray-500">Loading analytics...</div>
      </div>

      <!-- Dashboard Content -->
      <div v-else>
        <!-- KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <KPICard
            title="Total Searches"
            :value="overview.total_searches"
            icon="🔍"
          />
          <KPICard
            title="Avg Response Time"
            :value="overview.avg_response_time_ms"
            suffix="ms"
            icon="⚡"
          />
          <KPICard
            title="Success Rate"
            :value="overview.success_rate"
            suffix="%"
            icon="✅"
          />
          <KPICard
            title="Unique Sessions"
            :value="overview.unique_sessions"
            icon="👥"
          />
        </div>

        <!-- Charts Row 1 -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
          <TrendsChart :data="trends" />
          <StrategyDistribution :data="strategyDistribution" />
        </div>

        <!-- Charts Row 2 -->
        <div class="grid grid-cols-1 gap-6">
          <TopQueriesChart :data="topQueries" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
// biome-ignore lint/correctness/noUnusedImports: Components used in template
import KPICard from "../components/analytics/KPICard.vue"
// biome-ignore lint/correctness/noUnusedImports: Components used in template
import StrategyDistribution from "../components/analytics/StrategyDistribution.vue"
// biome-ignore lint/correctness/noUnusedImports: Components used in template
import TopQueriesChart from "../components/analytics/TopQueriesChart.vue"
// biome-ignore lint/correctness/noUnusedImports: Components used in template
import TrendsChart from "../components/analytics/TrendsChart.vue"

const isLoading = ref(false)
const selectedPeriod = ref("7")

const overview = ref({
	total_searches: 0,
	avg_response_time_ms: 0,
	success_rate: 0,
	click_through_rate: 0,
	unique_sessions: 0,
})

const trends = ref([])
const topQueries = ref([])

// biome-ignore lint/correctness/noUnusedVariables: Used in Vue template
const strategyDistribution = computed(() => {
	// Calculate from trends data
	const totals = { hybrid_ai: 0, exact: 0, prefix: 0 }

	trends.value.forEach((item) => {
		Object.keys(item.by_strategy).forEach((strategy) => {
			totals[strategy] = (totals[strategy] || 0) + item.by_strategy[strategy]
		})
	})

	return totals
})

const loadData = async () => {
	isLoading.value = true

	try {
		const days = selectedPeriod.value

		// Load all data in parallel
		const [overviewRes, trendsRes, queriesRes] = await Promise.all([
			fetch(`/api/analytics/overview?days=${days}`),
			fetch(`/api/analytics/trends?days=${days}`),
			fetch(`/api/analytics/top-queries?days=${days}`),
		])

		const [overviewData, trendsData, queriesData] = await Promise.all([
			overviewRes.json(),
			trendsRes.json(),
			queriesRes.json(),
		])

		overview.value = overviewData.data
		trends.value = trendsData.data
		topQueries.value = queriesData.data
	} catch (error) {
		console.error("Failed to load analytics:", error)
	} finally {
		isLoading.value = false
	}
}

onMounted(() => {
	loadData()
})
</script>
