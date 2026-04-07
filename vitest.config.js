import { resolve } from "node:path"
import vue from "@vitejs/plugin-vue"
import { defineConfig } from "vitest/config"

export default defineConfig({
	plugins: [vue()],
	resolve: {
		alias: {
			"@": resolve(import.meta.dirname, "assets"),
		},
	},
	test: {
		environment: "happy-dom",
		environmentOptions: {
			happyDOM: {
				settings: {
					navigation: {
						disableMainFrameNavigation: true,
						disableChildPageNavigation: true,
					},
				},
			},
		},
		globals: false,
		setupFiles: ["tests/Javascript/setup.js"],
		include: ["tests/Javascript/**/*.test.js"],
		exclude: ["node_modules", "public/build"],
		coverage: {
			provider: "v8",
			include: [
				"assets/components/**/*.vue",
				"assets/constants/**/*.js",
				"assets/services/**/*.js",
			],
			exclude: ["node_modules", "public/build"],
			thresholds: {
				lines: 80,
				functions: 80,
				branches: 80,
				statements: 80,
			},
		},
	},
})
