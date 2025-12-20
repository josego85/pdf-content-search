import "./styles/app.css"

import { createApp } from "vue"
import VueApexCharts from "vue3-apexcharts"
import Analytics from "./pages/Analytics.vue"

// Create Vue app for Analytics Dashboard
const app = createApp(Analytics)

// Register ApexCharts globally
app.use(VueApexCharts)

// Mount Analytics component
app.mount("#app")
