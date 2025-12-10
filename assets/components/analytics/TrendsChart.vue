<template>
  <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Search Volume Trends</h3>
    <apexchart
      v-if="chartOptions"
      type="line"
      height="300"
      :options="chartOptions"
      :series="series"
    />
    <div v-else class="h-64 flex items-center justify-center text-gray-500">
      Loading chart...
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  data: { type: Array, default: () => [] }
});

const series = computed(() => {
  if (!props.data.length) return [];

  const strategies = ['hybrid_ai', 'exact', 'prefix'];
  return strategies.map(strategy => ({
    name: strategy.replace('_', ' ').toUpperCase(),
    data: props.data.map(item => item.by_strategy[strategy] || 0)
  }));
});

const chartOptions = computed(() => {
  if (!props.data.length) return null;

  return {
    chart: {
      type: 'line',
      toolbar: { show: false },
      zoom: { enabled: false }
    },
    colors: ['#6366F1', '#10B981', '#F59E0B'],
    stroke: {
      curve: 'smooth',
      width: 4,
      dashArray: [0, 0, 0]
    },
    markers: {
      size: 5,
      strokeWidth: 2,
      hover: {
        size: 7
      }
    },
    xaxis: {
      categories: props.data.map(item => {
        const date = new Date(item.date);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
      })
    },
    yaxis: {
      title: { text: 'Searches' },
      labels: {
        formatter: (value) => Math.floor(value)
      }
    },
    legend: {
      position: 'top',
      horizontalAlign: 'right',
      markers: {
        width: 12,
        height: 12,
        radius: 2
      }
    },
    tooltip: {
      shared: true,
      intersect: false,
      y: {
        formatter: (value) => `${value} searches`
      }
    },
    grid: {
      borderColor: '#e5e7eb',
      strokeDashArray: 4
    }
  };
});
</script>
