<template>
  <div :class="viewMode === 'grid' ? 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-4 sm:gap-5 md:gap-6' : 'space-y-3 sm:space-y-4'">
    <ResultCard
      v-for="(result, index) in results"
      :key="result?._id || index"
      :result="result"
      :position="index + 1 + offset"
      :query="query"
      @open="$emit('open', result)"
    />
  </div>
</template>

<script>
import ResultCard from "./ResultCard.vue"

export default {
	name: "Results",
	components: {
		ResultCard,
	},
	props: {
		results: {
			type: Array,
			required: true,
		},
		offset: {
			type: Number,
			default: 0,
		},
		viewMode: {
			type: String,
			default: "grid",
			validator: (value) => ["grid", "list"].includes(value),
		},
		query: {
			type: String,
			required: true,
		},
	},
	emits: ["open"],
}
</script>
