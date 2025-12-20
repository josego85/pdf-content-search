<template>
  <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Search Strategy Usage</h3>
    <apexchart
      v-if="chartOptions && hasSeries"
      type="donut"
      height="300"
      :options="chartOptions"
      :series="series"
    />
    <div v-else class="h-64 flex items-center justify-center text-gray-500">
      No data available
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue"

const props = defineProps({
	data: { type: Object, default: () => ({}) },
})

const series = computed(() => {
	return [props.data.hybrid_ai || 0, props.data.exact || 0, props.data.prefix || 0]
})

// biome-ignore lint/correctness/noUnusedVariables: Used in Vue template
const hasSeries = computed(() => {
	return series.value.some((val) => val > 0)
})

// biome-ignore lint/correctness/noUnusedVariables: Used in Vue template
const chartOptions = computed(() => {
	return {
		chart: { type: "donut" },
		labels: ["Hybrid AI", "Exact", "Prefix"],
		colors: ["#3B82F6", "#10B981", "#F59E0B"],
		legend: {
			position: "bottom",
		},
		dataLabels: {
			enabled: true,
			formatter: (val) => `${val.toFixed(1)}%`,
		},
		plotOptions: {
			pie: {
				donut: {
					size: "65%",
					labels: {
						show: true,
						total: {
							show: true,
							label: "Total Searches",
							formatter: (w) => {
								return w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString()
							},
						},
					},
				},
			},
		},
	}
})
</script>
