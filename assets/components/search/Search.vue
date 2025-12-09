<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-gray-100">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 md:py-12">
      <!-- Hero Section -->
      <Hero />

      <!-- Search Box -->
      <Bar
        ref="searchBar"
        v-model="searchQuery"
        @clear="clearSearch"
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

      <!-- Loading State -->
      <Loading v-if="isLoading" />

      <!-- Results Grid/List -->
      <Results
        v-if="hasResults"
        :results="results"
        :view-mode="viewMode"
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
import Hero from './Hero.vue';
import Bar from './Bar.vue';
import Controls from './Controls.vue';
import Results from './Results.vue';
import Loading from './states/Loading.vue';
import Error from './states/Error.vue';
import Empty from './states/Empty.vue';
import Initial from './states/Initial.vue';

export default {
  name: 'Search',
  components: {
    Hero,
    Bar,
    Controls,
    Results,
    Loading,
    Error,
    Empty,
    Initial
  },
  data() {
    return {
      searchQuery: '',
      results: [],
      isLoading: false,
      error: null,
      debounceTimeout: null,
      searchDuration: null,
      viewMode: 'grid'
    }
  },
  computed: {
    hasResults() {
      return Array.isArray(this.results) && this.results.length > 0 && !this.isLoading;
    },
    showNoResults() {
      return this.searchQuery &&
             !this.isLoading &&
             Array.isArray(this.results) &&
             this.results.length === 0 &&
             !this.error;
    },
    showInitialState() {
      return !this.searchQuery && !this.isLoading && !this.hasResults;
    }
  },
  watch: {
    searchQuery() {
      this.handleSearch();
    }
  },
  mounted() {
    // Keyboard shortcuts
    document.addEventListener('keydown', this.handleKeyboardShortcuts);
  },
  beforeUnmount() {
    document.removeEventListener('keydown', this.handleKeyboardShortcuts);
  },
  methods: {
    handleKeyboardShortcuts(e) {
      // Focus search on '/' key
      if (e.key === '/' && !['INPUT', 'TEXTAREA'].includes(e.target.tagName)) {
        e.preventDefault();
        this.$refs.searchBar.focus();
      }
    },
    handleSearch() {
      clearTimeout(this.debounceTimeout);
      this.debounceTimeout = setTimeout(() => {
        if (this.searchQuery.length >= 2) {
          this.performSearch();
        } else {
          this.results = [];
          this.error = null;
          this.searchDuration = null;
        }
      }, 300);
    },
    async performSearch() {
      this.isLoading = true;
      this.error = null;
      const startTime = performance.now();

      try {
        const response = await fetch(`/api/search?q=${encodeURIComponent(this.searchQuery)}&strategy=hybrid_ai`);
        const data = await response.json();

        if (!response.ok) {
          throw new Error(data.message || 'Search failed');
        }

        if (data.status === 'success' && data.data?.hits) {
          this.results = data.data.hits;
          this.searchDuration = Math.round(performance.now() - startTime);
        } else {
          this.results = [];
          throw new Error('Invalid response format');
        }
      } catch (error) {
        console.error('Search error:', error);
        this.error = error.message;
        this.results = [];
      } finally {
        this.isLoading = false;
      }
    },
    clearSearch() {
      this.searchQuery = '';
      this.results = [];
      this.error = null;
      this.searchDuration = null;
    },
    openDocument(result) {
      window.open(this.getViewerLink(result), '_blank');
    },
    getViewerLink(result) {
      const path = encodeURIComponent(result._source?.path.replace('/pdfs/', ''));
      const page = result._source?.page;
      const highlights = result.highlight?.text || [];
      const highlightTerms = highlights
        .map(text => {
          const regex = /<mark>(.*?)<\/mark>/g;
          const matches = [];
          let match;

          while ((match = regex.exec(text)) !== null) {
            matches.push(match[1]);
          }
          return matches;
        })
        .flat();

      const highlightParam = encodeURIComponent(JSON.stringify(highlightTerms));
      return `/viewer?path=${path}&page=${page}&highlight=${highlightParam}`;
    }
  }
}
</script>
