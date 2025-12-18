<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-gray-100">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 md:py-12">
      <!-- Hero Section -->
      <Hero />

      <!-- Search Box -->
      <Bar
        ref="searchBar"
        v-model="searchQuery"
        :suggestions="suggestions"
        :show-suggestions="showSuggestions"
        :selected-index="selectedSuggestionIndex"
        @clear="clearSearch"
        @search="handleCommittedSearch"
        @select-suggestion="selectSuggestion"
        @navigate="navigateSuggestions"
        @focus="isFocused = true"
        @blur="handleBlur"
        @update:selected-index="selectedSuggestionIndex = $event"
      />

      <!-- Search Stats & View Toggle -->
      <Controls
        v-if="hasResults"
        :result-count="results.length"
        :duration="searchDuration"
        :view-mode="viewMode"
        @update:view-mode="viewMode = $event"
      />

      <!-- Error Message -->
      <Error
        v-if="error"
        :message="error"
        @close="error = null"
      />

      <!-- Loading State (only for suggestions) -->
      <Loading v-if="isLoadingSuggestions" />

      <!-- Results Grid/List (only shown after committed search) -->
      <Results
        v-if="hasResults"
        :results="results"
        :view-mode="viewMode"
        :query="searchQuery"
        @open="openDocument"
      />

      <!-- Empty State -->
      <Empty
        v-if="showNoResults"
        :search-query="searchQuery"
        @clear="clearSearch"
      />

      <!-- Initial State -->
      <Initial v-if="showInitialState" />
    </div>
  </div>
</template>

<script>
import Bar from "./Bar.vue"
import Controls from "./Controls.vue"
import Hero from "./Hero.vue"
import Results from "./Results.vue"
import Empty from "./states/Empty.vue"
import Error from "./states/Error.vue"
import Initial from "./states/Initial.vue"
import Loading from "./states/Loading.vue"

export default {
	name: "Search",
	components: {
		Hero,
		Bar,
		Controls,
		Results,
		Loading,
		Error,
		Empty,
		Initial,
	},
	data() {
		return {
			searchQuery: "",
			suggestions: [],
			results: [],
			isLoadingSuggestions: false,
			error: null,
			debounceTimeout: null,
			searchDuration: null,
			viewMode: "grid",
			searchStrategy: "hybrid_ai",
			isFocused: false,
			selectedSuggestionIndex: -1,
			blurTimeout: null,
		}
	},
	computed: {
		hasResults() {
			return Array.isArray(this.results) && this.results.length > 0
		},
		showSuggestions() {
			return (
				this.isFocused &&
				this.suggestions.length > 0 &&
				this.searchQuery.length >= 2 &&
				this.results.length === 0
			) // Only show suggestions when no full results
		},
		showNoResults() {
			return (
				this.searchQuery &&
				!this.isLoadingSuggestions &&
				Array.isArray(this.results) &&
				this.results.length === 0 &&
				!this.error &&
				this.suggestions.length === 0
			)
		},
		showInitialState() {
			return !this.searchQuery && !this.isLoadingSuggestions && !this.hasResults
		},
	},
	watch: {
		searchQuery() {
			this.handleSearch()
		},
	},
	mounted() {
		// Keyboard shortcuts
		document.addEventListener("keydown", this.handleKeyboardShortcuts)
	},
	beforeUnmount() {
		document.removeEventListener("keydown", this.handleKeyboardShortcuts)
	},
	methods: {
		handleKeyboardShortcuts(e) {
			// Focus search on '/' key
			if (e.key === "/" && !["INPUT", "TEXTAREA"].includes(e.target.tagName)) {
				e.preventDefault()
				this.$refs.searchBar.focus()
			}
		},
		handleCommittedSearch() {
			// User pressed ENTER or selected a suggestion - execute full search with logging
			if (this.selectedSuggestionIndex >= 0 && this.suggestions[this.selectedSuggestionIndex]) {
				// User selected a suggestion with arrow keys
				this.selectSuggestion(this.suggestions[this.selectedSuggestionIndex])
			} else {
				// User pressed ENTER without selecting a suggestion
				clearTimeout(this.debounceTimeout)
				if (this.searchQuery.length >= 2) {
					this.suggestions = [] // Clear suggestions
					this.performFullSearch()
				}
			}
		},
		handleSearch() {
			// Auto-fetch suggestions while typing
			clearTimeout(this.debounceTimeout)
			this.debounceTimeout = setTimeout(() => {
				if (this.searchQuery.length >= 2) {
					this.fetchSuggestions()
				} else {
					this.suggestions = []
					this.results = []
					this.error = null
					this.searchDuration = null
				}
			}, 300)
		},
		async fetchSuggestions() {
			// Fetch quick suggestions (NO logging, limit to 10 results)
			this.isLoadingSuggestions = true
			this.error = null

			try {
				const response = await fetch(
					`/api/search?q=${encodeURIComponent(this.searchQuery)}&strategy=${this.searchStrategy}&log=0`,
				)
				const data = await response.json()

				if (!response.ok) {
					throw new Error(data.message || "Search failed")
				}

				if (data.status === "success" && data.data?.hits) {
					this.suggestions = data.data.hits.slice(0, 10) // Limit to 10 suggestions
				} else {
					this.suggestions = []
				}
			} catch (error) {
				console.error("Suggestions error:", error)
				this.suggestions = []
			} finally {
				this.isLoadingSuggestions = false
			}
		},
		async performFullSearch() {
			// Full search with analytics logging
			this.isLoadingSuggestions = true
			this.error = null
			const startTime = performance.now()

			try {
				const response = await fetch(
					`/api/search?q=${encodeURIComponent(this.searchQuery)}&strategy=${this.searchStrategy}&log=1`,
				)
				const data = await response.json()

				if (!response.ok) {
					throw new Error(data.message || "Search failed")
				}

				if (data.status === "success" && data.data?.hits) {
					this.results = data.data.hits
					this.searchDuration = data.data.duration_ms || Math.round(performance.now() - startTime)
					// Don't update searchStrategy - let backend auto-detect each time
					this.suggestions = [] // Clear suggestions after full search
				} else {
					this.results = []
					throw new Error("Invalid response format")
				}
			} catch (error) {
				console.error("Search error:", error)
				this.error = error.message
				this.results = []
			} finally {
				this.isLoadingSuggestions = false
			}
		},
		selectSuggestion(_suggestion) {
			// User clicked on a suggestion - execute full search with logging
			// Keep the original search query unchanged
			this.suggestions = []
			this.selectedSuggestionIndex = -1
			this.performFullSearch()
		},
		navigateSuggestions(direction) {
			if (direction === "down") {
				this.selectedSuggestionIndex = Math.min(
					this.selectedSuggestionIndex + 1,
					this.suggestions.length - 1,
				)
			} else if (direction === "up") {
				this.selectedSuggestionIndex = Math.max(this.selectedSuggestionIndex - 1, -1)
			}
		},
		handleBlur() {
			// Delay hiding suggestions to allow click events
			this.blurTimeout = setTimeout(() => {
				this.isFocused = false
				this.selectedSuggestionIndex = -1
			}, 200)
		},
		clearSearch() {
			this.searchQuery = ""
			this.suggestions = []
			this.results = []
			this.error = null
			this.searchDuration = null
			this.selectedSuggestionIndex = -1
			clearTimeout(this.blurTimeout)
		},
		openDocument(result) {
			window.open(this.getViewerLink(result), "_blank")
		},
		getViewerLink(result) {
			const path = encodeURIComponent(result._source?.path.replace("/pdfs/", ""))
			const page = result._source?.page
			const highlights = result.highlight?.text || []
			const highlightTerms = highlights.flatMap((text) => {
				const regex = /<mark>(.*?)<\/mark>/g
				const matches = []
				let match

				while ((match = regex.exec(text)) !== null) {
					matches.push(match[1])
				}
				return matches
			})

			const highlightParam = encodeURIComponent(JSON.stringify(highlightTerms))
			return `/viewer?path=${path}&page=${page}&highlight=${highlightParam}`
		},
	},
}
</script>
