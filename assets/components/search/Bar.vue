<template>
  <div class="max-w-3xl mx-auto mb-6 sm:mb-8">
    <div class="relative group">
      <div class="absolute inset-y-0 left-0 pl-3 sm:pl-4 md:pl-5 flex items-center pointer-events-none">
        <svg class="h-5 w-5 sm:h-6 sm:w-6 text-gray-400 group-focus-within:text-blue-600 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </div>
      <input
        ref="searchInput"
        type="text"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        @keydown.esc="handleEscape"
        @keydown.enter="handleEnter"
        @keydown.down.prevent="$emit('navigate', 'down')"
        @keydown.up.prevent="$emit('navigate', 'up')"
        @focus="$emit('focus')"
        @blur="$emit('blur')"
        placeholder="Search documents..."
        class="block w-full pl-10 sm:pl-12 md:pl-14 pr-12 sm:pr-16 md:pr-20 py-3 sm:py-4 md:py-5 bg-white border-2 border-gray-200 rounded-xl sm:rounded-2xl text-base sm:text-lg placeholder-gray-400
               focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10
               transition-all duration-200 shadow-lg shadow-gray-200/50 hover:shadow-xl hover:shadow-gray-300/50"
      />
      <div class="absolute inset-y-0 right-0 pr-2 sm:pr-3 md:pr-4 flex items-center space-x-1 sm:space-x-2">
        <button
          v-if="modelValue"
          @click="$emit('clear')"
          class="p-2 sm:p-2.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors touch-manipulation"
          title="Clear search (Esc)"
          aria-label="Clear search"
        >
          <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
        <kbd class="hidden md:inline-flex items-center px-2 py-1 text-xs font-semibold text-gray-500 bg-gray-100 border border-gray-200 rounded">
          ESC
        </kbd>
      </div>

      <!-- Suggestions Dropdown -->
      <Suggestions
        v-if="showSuggestions"
        :suggestions="suggestions"
        :selected-index="selectedIndex"
        @select="$emit('select-suggestion', $event)"
        @update:selected-index="$emit('update:selectedIndex', $event)"
      />
    </div>
  </div>
</template>

<script>
import Suggestions from "./Suggestions.vue"

export default {
	name: "Bar",
	components: {
		Suggestions,
	},
	props: {
		modelValue: {
			type: String,
			required: true,
		},
		suggestions: {
			type: Array,
			default: () => [],
		},
		showSuggestions: {
			type: Boolean,
			default: false,
		},
		selectedIndex: {
			type: Number,
			default: -1,
		},
	},
	emits: [
		"update:modelValue",
		"clear",
		"search",
		"select-suggestion",
		"navigate",
		"focus",
		"blur",
		"update:selectedIndex",
	],
	methods: {
		focus() {
			this.$refs.searchInput.focus()
		},
		handleEscape() {
			if (this.showSuggestions) {
				this.$emit("clear")
			} else {
				this.$emit("clear")
			}
		},
		handleEnter() {
			this.$emit("search")
		},
	},
}
</script>
