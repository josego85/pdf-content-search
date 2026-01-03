const Encore = require("@symfony/webpack-encore")

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
	Encore.configureRuntimeEnvironment(process.env.NODE_ENV || "dev")
}

Encore
	// directory where compiled assets will be stored
	.setOutputPath("public/build/")
	// public path used by the web server to access the output path
	.setPublicPath("/build")
	// only needed for CDN's or subdirectory deploy
	//.setManifestKeyPrefix('build/')

	/*
	 * ENTRY CONFIG
	 *
	 * Each entry will result in one JavaScript file (e.g. app.js)
	 * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
	 */
	.addEntry("app", "./assets/app.js")
	.addEntry("pdfViewer", "./assets/pdfViewer.js")
	.addEntry("analytics", "./assets/analytics.js")

	.enableVueLoader(() => {}, {
		version: 3,
		runtimeCompilerBuild: false, // Runtime-only build for smaller bundle size (~33KB reduction)
	})

	.enablePostCssLoader()

	// When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
	.splitEntryChunks()

	// will require an extra script tag for runtime.js
	// but, you probably want this, unless you're building a single-page app
	.enableSingleRuntimeChunk()

	// Advanced code splitting for large vendors
	.configureSplitChunks((splitChunks) => {
		splitChunks.chunks = "all"
		splitChunks.maxSize = 244000 // 244KB - recommended max size
		splitChunks.cacheGroups = {
			// PDF.js is HUGE (~600KB) - separate it
			pdfjs: {
				test: /[\\/]node_modules[\\/]pdfjs-dist/,
				name: "vendor-pdfjs",
				priority: 20,
				reuseExistingChunk: true,
				enforce: true,
			},
			// ApexCharts is large (~400KB) - separate it
			apexcharts: {
				test: /[\\/]node_modules[\\/]apexcharts/,
				name: "vendor-charts",
				priority: 15,
				reuseExistingChunk: true,
				enforce: true,
			},
			// Vue framework
			vue: {
				test: /[\\/]node_modules[\\/](vue|@vue)/,
				name: "vendor-vue",
				priority: 10,
				reuseExistingChunk: true,
			},
			// Common vendors used across multiple entries
			vendors: {
				test: /[\\/]node_modules[\\/]/,
				name: "vendors",
				priority: 5,
				minChunks: 2, // Only if used in 2+ entries
				reuseExistingChunk: true,
			},
		}
	})

	/*
	 * FEATURE CONFIG
	 *
	 * Enable & configure other features below. For a full
	 * list of features, see:
	 * https://symfony.com/doc/current/frontend.html#adding-more-features
	 */
	.cleanupOutputBeforeBuild()
	.enableBuildNotifications()
	.enableSourceMaps(!Encore.isProduction())
	// enables hashed filenames (e.g. app.abc123.css)
	.enableVersioning(Encore.isProduction())

	// configure Babel
	// .configureBabel((config) => {
	//     config.plugins.push('@babel/a-babel-plugin');
	// })

	// enables and configure @babel/preset-env polyfills
	.configureBabelPresetEnv((config) => {
		config.useBuiltIns = "usage"
		config.corejs = "3.38"
	})

	// enables Sass/SCSS support
	//.enableSassLoader()

	// uncomment if you use TypeScript
	//.enableTypeScriptLoader()

	// uncomment if you use React
	//.enableReactPreset()

	// uncomment to get integrity="..." attributes on your script & link tags
	// requires WebpackEncoreBundle 1.4 or higher
	//.enableIntegrityHashes(Encore.isProduction())

	// uncomment if you're having problems with a jQuery plugin
	//.autoProvidejQuery()
	// webpack.config.js (ejemplo avanzado)

	.enableSassLoader()

Encore.configureDefinePlugin((options) => {
	options.__VUE_OPTIONS_API__ = true
	options.__VUE_PROD_DEVTOOLS__ = false
})

Encore.copyFiles({
	from: "./node_modules/pdfjs-dist/build",
	to: "[name].[ext]",
	pattern: /pdf\.worker\.(mjs|js|map)$/,
})

module.exports = Encore.getWebpackConfig()
