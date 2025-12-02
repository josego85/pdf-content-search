<template>
  <article
    class="group bg-white rounded-xl sm:rounded-2xl border-2 border-gray-100 hover:border-blue-200
           p-4 sm:p-5 md:p-6 transition-all duration-300 hover:shadow-xl hover:shadow-blue-100/50
           hover:-translate-y-1 cursor-pointer"
    @click="$emit('open', result)"
  >
    <!-- Header -->
    <div class="mb-3 sm:mb-4">
      <h3 class="text-base sm:text-lg font-bold text-gray-900 group-hover:text-blue-600 transition-colors line-clamp-2 mb-2">
        {{ result._source?.title }}
      </h3>
      <div class="flex items-center flex-wrap gap-1.5 sm:gap-2 text-xs sm:text-sm">
        <span class="inline-flex items-center px-2 sm:px-3 py-1 rounded-full bg-blue-50 text-blue-700 font-medium">
          <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          PDF
        </span>
        <span class="inline-flex items-center px-2 sm:px-3 py-1 rounded-full bg-gray-100 text-gray-700">
          Page {{ result._source?.page }}/{{ result._source?.total_pages }}
        </span>
        <span v-if="result._source?.language" class="inline-flex items-center px-2 sm:px-3 py-1 rounded-full bg-green-50 text-green-700 font-medium">
          <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-0.5 sm:mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
          </svg>
          {{ getLanguageLabel(result._source?.language) }}
        </span>
        <span class="inline-flex items-center px-2 sm:px-3 py-1 rounded-full bg-purple-50 text-purple-700 font-medium">
          <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-0.5 sm:mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
          </svg>
          Score: {{ formatScore(result._score) }}
        </span>
      </div>
    </div>

    <!-- Content Preview -->
    <div v-if="result.highlight?.text" class="mb-3 sm:mb-4">
      <p class="text-gray-600 text-xs sm:text-sm leading-relaxed line-clamp-3" v-html="result.highlight.text.join(' ... ')"></p>
    </div>

    <!-- Footer -->
    <div class="flex items-center justify-between pt-3 sm:pt-4 border-t border-gray-100 gap-2">
      <div class="flex items-center text-xs sm:text-sm text-gray-500 min-w-0">
        <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 mr-1 sm:mr-1.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <span class="truncate">{{ formatDate(result._source?.date) }}</span>
      </div>
      <a
        :href="viewerLink"
        target="_blank"
        rel="noopener"
        @click.stop
        class="inline-flex items-center px-3 sm:px-4 py-2 sm:py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs sm:text-sm font-medium rounded-lg
               transition-all duration-200 shadow-lg shadow-blue-600/20 hover:shadow-xl hover:shadow-blue-600/30
               group-hover:scale-105 touch-manipulation flex-shrink-0"
        aria-label="View PDF"
      >
        <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 sm:mr-2" :class="{'mr-0': true, 'sm:mr-2': true}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        </svg>
        <span class="hidden sm:inline">View PDF</span>
      </a>
    </div>
  </article>
</template>

<script>
import { getLanguageLabel } from '../../constants/languages.js';

export default {
  name: 'ResultCard',
  props: {
    result: {
      type: Object,
      required: true
    }
  },
  emits: ['open'],
  computed: {
    viewerLink() {
      const path = encodeURIComponent(this.result._source?.path.replace('/pdfs/', ''));
      const page = this.result._source?.page;
      const highlights = this.result.highlight?.text || [];
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
  },
  methods: {
    formatScore(score) {
      return Math.round(score * 100) / 100;
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
    },
    getLanguageLabel(code) {
      return getLanguageLabel(code);
    }
  }
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

mark {
  @apply bg-yellow-100 text-yellow-900 px-1 rounded font-medium;
}
</style>
