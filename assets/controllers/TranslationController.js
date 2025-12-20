/**
 * Translation Controller
 * Orchestrates translation workflow between API service and UI.
 * Single Responsibility: Translation business logic and workflow.
 */

import { getLanguageName } from "../constants/languages.js"
import { TranslationApiService } from "../services/TranslationApiService.js"

export class TranslationController {
	constructor(ui, pdfPath, pageNumber) {
		this.apiService = new TranslationApiService()
		this.ui = ui
		this.pdfPath = pdfPath
		this.pageNumber = pageNumber

		this._setupEventHandlers()
	}

	/**
	 * Setup event handlers
	 */
	_setupEventHandlers() {
		this.ui.onTranslateClick(() => this.handleTranslateClick())
	}

	/**
	 * Handle translate button click
	 */
	async handleTranslateClick() {
		const targetLanguage = this.ui.getSelectedLanguage()

		this.ui.setTranslateButtonState(true)
		this.ui.updateStatus("Iniciando traducción...", "loading")

		try {
			const result = await this.apiService.requestTranslation(
				this.pdfPath,
				this.pageNumber,
				targetLanguage,
			)

			// Translation available immediately (from cache/DB or already in target language)
			if (result.status === "success") {
				this._handleImmediateTranslation(result.data, targetLanguage)
				return
			}

			// Translation queued for async processing
			if (result.status === "queued") {
				await this._handleQueuedTranslation(targetLanguage)
				return
			}

			throw new Error(result.message || "Error desconocido")
		} catch (error) {
			console.error("Translation error:", error)
			this.ui.updateStatus(`Error: ${error.message}`, "error")
		} finally {
			this.ui.setTranslateButtonState(false)
		}
	}

	/**
	 * Handle immediate translation (from cache/DB)
	 */
	_handleImmediateTranslation(data, targetLanguage) {
		let statusText = ""

		if (data.source === "original") {
			statusText = `Ya está en ${getLanguageName(targetLanguage)}`
			this.ui.updateStatus(statusText, "info")
			this.ui.showTranslation(data.original_text)
		} else {
			statusText = `Traducido de ${getLanguageName(data.source_language)} a ${getLanguageName(targetLanguage)}`

			// Add source indicator
			if (data.cached) {
				statusText += " (caché)"
			} else if (data.source === "database") {
				statusText += " (BD)"
			}

			this.ui.updateStatus(statusText, "success")
			this.ui.showTranslation(data.translated_text)
		}
	}

	/**
	 * Handle queued translation (async processing)
	 */
	async _handleQueuedTranslation(targetLanguage) {
		this.ui.updateStatus("Procesando traducción...", "loading")

		const result = await this.apiService.pollForTranslation(
			this.pdfPath,
			this.pageNumber,
			targetLanguage,
		)

		const data = result.data
		const statusText = `Traducido de ${getLanguageName(data.source_language)} a ${getLanguageName(targetLanguage)} (IA)`

		this.ui.updateStatus(statusText, "success")
		this.ui.showTranslation(data.translated_text)
	}
}
