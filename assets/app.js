import "./styles/app.css"
import "./styles/pdfViewer.scss"

import { createApp } from "vue"
import Search from "./components/search/Search.vue"

// Mount Search component directly (required for runtime-only build)
const app = createApp(Search)

app.mount("#app")
