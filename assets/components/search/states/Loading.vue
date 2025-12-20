<template>
  <div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl sm:rounded-2xl shadow-lg p-8 sm:p-10 md:p-12">
      <div class="flex flex-col items-center justify-center">
        <div class="relative">
          <div class="w-14 h-14 sm:w-16 sm:h-16 border-4 border-blue-100 rounded-full"></div>
          <div class="w-14 h-14 sm:w-16 sm:h-16 border-4 border-blue-600 rounded-full animate-spin border-t-transparent absolute top-0 left-0"></div>
        </div>
        <p class="mt-5 sm:mt-6 text-sm sm:text-base text-gray-600 font-medium">Searching documents...</p>
        <p class="mt-2 text-xs sm:text-sm text-gray-400">This should only take a moment</p>
      </div>
    </div>
  </div>
</template>

<script>
export default {
	name: "Loading",
}
</script>
