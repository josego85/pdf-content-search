<template>
  <div :class="viewMode === 'grid' ? 'grid grid-cols-1 md:grid-cols-2 gap-6' : 'space-y-4'">
    <ResultCard
      v-for="(result, index) in results"
      :key="result?._id || index"
      :result="result"
      @open="$emit('open', result)"
    />
  </div>
</template>

<script>
import ResultCard from './ResultCard.vue';

export default {
  name: 'Results',
  components: {
    ResultCard
  },
  props: {
    results: {
      type: Array,
      required: true
    },
    viewMode: {
      type: String,
      default: 'grid',
      validator: (value) => ['grid', 'list'].includes(value)
    }
  },
  emits: ['open']
}
</script>
