<template>
  <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
    <div class="flex items-start justify-between mb-1">
      <h3 class="text-lg font-semibold text-gray-900">Click Position Distribution</h3>
    </div>

    <template v-if="hasData">
      <!-- Key insights -->
      <div class="flex gap-4 text-xs text-gray-500 mb-4">
        <span>Position #1 CTR: <strong class="text-gray-800">{{ pos1Ctr }}%</strong></span>
        <span>Top 3 capture: <strong class="text-gray-800">{{ top3Pct }}%</strong> of clicks</span>
      </div>

      <apexchart type="bar" height="260" :options="chartOptions" :series="series" />
    </template>

    <div v-else class="h-64 flex items-center justify-center text-gray-500">
      No click data available
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue"

const props = defineProps({
	data: { type: Array, default: () => [] },
})

// biome-ignore lint/correctness/noUnusedVariables: Used in Vue template
const hasData = computed(() => props.data.length > 0)

// biome-ignore lint/correctness/noUnusedVariables: Used in Vue template
const pos1Ctr = computed(() => props.data.find((d) => d.position === 1)?.ctr ?? 0)

// biome-ignore lint/correctness/noUnusedVariables: Used in Vue template
const top3Pct = computed(() => {
	const total = props.data.reduce((sum, d) => sum + d.clicks, 0)
	if (total === 0) {
		return 0
	}
	const top3 = props.data.filter((d) => d.position <= 3).reduce((sum, d) => sum + d.clicks, 0)
	return Math.round((top3 / total) * 100)
})

// biome-ignore lint/correctness/noUnusedVariables: Used in Vue template
const series = computed(() => [{ name: "CTR", data: props.data.map((d) => d.ctr) }])

// biome-ignore lint/correctness/noUnusedVariables: Used in Vue template
const chartOptions = computed(() => ({
	chart: { type: "bar", toolbar: { show: false } },
	xaxis: {
		categories: props.data.map((d) => `#${d.position}`),
		title: { text: "Result Position" },
	},
	yaxis: {
		title: { text: "CTR %" },
		min: 0,
		labels: { formatter: (val) => `${val}%` },
	},
	colors: ["#3B82F6"],
	plotOptions: { bar: { borderRadius: 4, columnWidth: "60%" } },
	dataLabels: { enabled: false },
	tooltip: {
		custom: ({ dataPointIndex }) => {
			const d = props.data[dataPointIndex]
			return `<div style="padding:8px 12px;font-size:13px">
        <div style="font-weight:600;margin-bottom:4px">Position #${d.position}</div>
        <div>CTR: <strong>${d.ctr}%</strong></div>
        <div style="color:#6b7280">${d.clicks} clicks / ${d.impressions} impressions</div>
      </div>`
		},
	},
}))
</script>
