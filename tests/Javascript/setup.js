import { config } from "@vue/test-utils"

// Stub apexchart globally — registered as a native tag by vue3-apexcharts plugin.
// Without this stub, happy-dom warns "Failed to resolve component: apexchart" in all chart tests.
config.global.stubs = {
	apexchart: { template: '<div data-testid="apexchart" />' },
}
