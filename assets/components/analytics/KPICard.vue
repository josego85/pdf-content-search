<template>
  <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-gray-600">{{ title }}</p>
        <p class="text-3xl font-bold text-gray-900 mt-2">{{ formattedValue }}</p>
        <p v-if="trend !== null" class="text-sm mt-2" :class="trendClass">
          <span>{{ trend > 0 ? '↑' : '↓' }}</span>
          {{ Math.abs(trend) }}% vs previous period
        </p>
      </div>
      <div class="text-4xl" v-if="icon">{{ icon }}</div>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue"

const props = defineProps({
	title: { type: String, required: true },
	value: { type: [Number, String], required: true },
	trend: { type: Number, default: null },
	icon: { type: String, default: "" },
	suffix: { type: String, default: "" },
})

const _formattedValue = computed(() => {
	if (typeof props.value === "number") {
		return props.value.toLocaleString() + props.suffix
	}
	return props.value
})

const _trendClass = computed(() => {
	if (props.trend === null) {
		return ""
	}
	return props.trend > 0 ? "text-green-600" : "text-red-600"
})
</script>
