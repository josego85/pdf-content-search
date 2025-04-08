<template>
  <div class="max-w-4xl mx-auto p-6">
    <div class="search-container bg-white shadow-lg rounded-xl p-8">
      <!-- Search Header -->
      <h2 class="text-2xl font-bold text-gray-900 mb-6">PDF Document Search</h2>

      <!-- Search Input -->
      <div class="relative mb-6">
        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
          <svg class="h-5 w-5 text-gray-400 transition-colors group-hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
        <input 
          type="text" 
          v-model="searchQuery" 
          @input="handleSearch"
          placeholder="Search in PDF documents..."
          class="block w-full pl-12 pr-4 py-3 border border-gray-200 rounded-lg leading-5 bg-gray-50 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out"
        />
      </div>

      <!-- Error Message -->
      <div v-if="error" class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-r-lg">
        <div class="flex items-center">
          <svg class="h-5 w-5 text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <p class="text-sm font-medium text-red-800">{{ error }}</p>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="my-8 flex justify-center">
        <div class="animate-spin rounded-full h-10 w-10 border-t-2 border-b-2 border-blue-500"></div>
      </div>

      <!-- Results -->
      <div v-if="hasResults" class="mt-6 space-y-4">
        <div v-for="(result, index) in results" 
             :key="result?._id || index" 
             class="result-item bg-white rounded-lg border border-gray-100 p-6 hover:shadow-md hover:border-blue-100 transition-all duration-200">
             <h3 class="text-lg font-semibold text-gray-900 mb-2">
                {{ result._source?.title }}
              </h3>
              <div class="text-sm text-gray-500 mb-2">
                📄 Page {{ result._source?.page }} of {{ result._source?.total_pages }}
              </div>
              <a
                :href="`${result._source?.path}#page=${result._source?.page}`"
                target="_blank"
                rel="noopener"
                class="text-blue-600 hover:underline text-sm mb-2 inline-block"
              >
                🔎 View PDF at this page
              </a>
          <p v-if="result.highlight?.content" 
             class="text-gray-600 text-sm leading-relaxed mb-3"
             v-html="result.highlight.content.join('...')">
          </p>
          <div class="flex items-center space-x-3 text-sm">
            <span class="text-gray-500">
              <svg class="h-4 w-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              {{ formatDate(result._source?.date) }}
            </span>
            <span class="inline-flex items-center px-3 py-0.5 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
              PDF
            </span>
            <span class="text-gray-400 flex items-center">
              <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              {{ Math.round(result._score * 100) / 100 }}
            </span>
          </div>
        </div>
      </div>

      <!-- No Results State -->
      <div v-if="showNoResults" 
           class="mt-8 text-center py-12 bg-gray-50 rounded-xl border-2 border-dashed border-gray-200">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="mt-4 text-gray-600">No results found for "<span class="font-medium">{{ searchQuery }}</span>"</p>
        <p class="mt-2 text-sm text-gray-500">Try adjusting your search terms</p>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'SearchComponent',
  data() {
    return {
      searchQuery: '',
      results: [],
      isLoading: false,
      error: null,
      debounceTimeout: null
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
    }
  },
  methods: {
    handleSearch() {
      clearTimeout(this.debounceTimeout);
      this.debounceTimeout = setTimeout(() => {
        if (this.searchQuery.length >= 2) {
          this.performSearch();
        } else {
          this.results = [];
          this.error = null;
        }
      }, 300);
    },
    async performSearch() {
      this.isLoading = true;
      this.error = null;
      
      try {
        const response = await fetch(`/api/search?q=${encodeURIComponent(this.searchQuery)}`);
        const data = await response.json();
        
        if (!response.ok) {
          throw new Error(data.message || 'Search failed');
        }

        if (data.status === 'success' && data.data?.hits) {
          this.results = data.data.hits;
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
    formatDate(dateString) {
      if (!dateString) return '';
      try {
        return new Date(dateString).toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        });
      } catch (e) {
        return dateString;
      }
    }
  }
}
</script>

<style>
.result-item mark {
  @apply bg-yellow-100 text-yellow-900 px-1 rounded font-medium;
}

.result-item:hover {
  @apply transform -translate-y-1 shadow-lg;
}

.search-container {
  @apply transition-all duration-300 ease-in-out;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>