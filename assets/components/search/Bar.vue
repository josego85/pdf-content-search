<template>
  <div class="max-w-3xl mx-auto mb-8">
    <div class="relative group">
      <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
        <svg class="h-6 w-6 text-gray-400 group-focus-within:text-blue-600 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </div>
      <input
        ref="searchInput"
        type="text"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        @keydown.esc="$emit('clear')"
        placeholder="Search documents... (Press / to focus)"
        class="block w-full pl-14 pr-32 py-5 bg-white border-2 border-gray-200 rounded-2xl text-lg placeholder-gray-400
               focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10
               transition-all duration-200 shadow-lg shadow-gray-200/50 hover:shadow-xl hover:shadow-gray-300/50"
      />
      <div class="absolute inset-y-0 right-0 pr-4 flex items-center space-x-2">
        <button
          v-if="modelValue"
          @click="$emit('clear')"
          class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
          title="Clear search (Esc)"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
        <kbd class="hidden md:inline-flex items-center px-2 py-1 text-xs font-semibold text-gray-500 bg-gray-100 border border-gray-200 rounded">
          ESC
        </kbd>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Bar',
  props: {
    modelValue: {
      type: String,
      required: true
    }
  },
  emits: ['update:modelValue', 'clear'],
  methods: {
    focus() {
      this.$refs.searchInput.focus();
    }
  }
}
</script>
