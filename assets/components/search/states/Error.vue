<template>
  <div class="max-w-3xl mx-auto mb-8">
    <div class="bg-red-50 border-l-4 border-red-500 rounded-r-xl p-5 shadow-lg animate-shake">
      <div class="flex items-start">
        <div class="flex-shrink-0">
          <svg class="h-6 w-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        <div class="ml-4">
          <h3 class="text-sm font-medium text-red-800">Error occurred</h3>
          <p class="mt-1 text-sm text-red-700">{{ message }}</p>
        </div>
        <button @click="$emit('close')" class="ml-auto flex-shrink-0 text-red-400 hover:text-red-600">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Error',
  props: {
    message: {
      type: String,
      required: true
    }
  },
  emits: ['close']
}
</script>

<style scoped>
@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-10px); }
  75% { transform: translateX(10px); }
}

.animate-shake {
  animation: shake 0.5s ease-in-out;
}
</style>
